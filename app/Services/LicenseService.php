<?php

namespace App\Services;

use App\Models\License;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Exception;

class LicenseService
{
    /**
     * التحقق من وجود ترخيص مفعل
     */
    public static function isActivated(): bool
    {
        if (!config('license.enabled')) {
            return true; // إذا كان النظام معطلاً، نعتبره مفعل
        }

        $license = self::getCurrentLicense();
        return $license && $license->isValid();
    }

    /**
     * الحصول على الترخيص الحالي
     */
    public static function getCurrentLicense(): ?License
    {
        $cacheKey = 'current_license_' . self::getCurrentDomain();
        
        return Cache::remember($cacheKey, 3600, function () {
            // استخدام Connection الافتراضي (يتم تبديله تلقائياً في AppServiceProvider)
            $domain = self::getCurrentDomain();
            
            try {
                $license = License::where('domain', $domain)
                    ->where('is_active', true)
                    ->first();

                if ($license) {
                    return $license;
                }
            } catch (\Exception $e) {
                // إذا فشل في Connection الافتراضي، جرب SQLite مباشرة
                Log::debug('Failed to get license from default connection, trying SQLite', [
                    'error' => $e->getMessage()
                ]);
                
                try {
                    $license = License::on('sync_sqlite')
                        ->where('domain', $domain)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($license) {
                        return $license;
                    }
                } catch (\Exception $e2) {
                    Log::warning('Failed to get license from SQLite', [
                        'error' => $e2->getMessage()
                    ]);
                }
            }

            // محاولة الحصول من ملف الترخيص
            return self::loadFromFile();
        });
    }

    /**
     * تفعيل الترخيص
     */
    public static function activate(string $licenseKey, ?string $domain = null, ?string $fingerprint = null): array
    {
        try {
            $domain = $domain ?? self::getCurrentDomain();
            $fingerprint = $fingerprint ?? self::getServerFingerprint();

            // فك تشفير المفتاح
            $licenseData = self::decryptLicenseKey($licenseKey);

            if (!$licenseData) {
                return [
                    'success' => false,
                    'message' => 'مفتاح الترخيص غير صحيح أو تالف'
                ];
            }

            // التحقق من Domain
            if (isset($licenseData['domain']) && $licenseData['domain'] !== $domain) {
                return [
                    'success' => false,
                    'message' => 'مفتاح الترخيص غير صالح لهذا السيرفر'
                ];
            }

            // التحقق من Fingerprint (إذا كان موجوداً)
            if (isset($licenseData['fingerprint']) && $licenseData['fingerprint'] !== $fingerprint) {
                return [
                    'success' => false,
                    'message' => 'مفتاح الترخيص غير صالح لهذا السيرفر'
                ];
            }

            // التحقق من انتهاء الترخيص
            if (isset($licenseData['expires_at']) && strtotime($licenseData['expires_at']) < time()) {
                return [
                    'success' => false,
                    'message' => 'مفتاح الترخيص منتهي الصلاحية'
                ];
            }

            // حفظ الترخيص في Database
            $license = License::updateOrCreate(
                ['domain' => $domain],
                [
                    'license_key' => Crypt::encryptString($licenseKey),
                    'fingerprint' => $fingerprint,
                    'type' => $licenseData['type'] ?? 'standard',
                    'max_installations' => $licenseData['max_installations'] ?? 1,
                    'activated_at' => now(),
                    'expires_at' => isset($licenseData['expires_at']) ? $licenseData['expires_at'] : null,
                    'is_active' => true,
                    'last_verified_at' => now(),
                ]
            );

            // حفظ في ملف
            self::saveToFile($license);

            // مسح الكاش
            Cache::forget('current_license');

            Log::info('License activated successfully', [
                'domain' => $domain,
                'type' => $license->type
            ]);

            return [
                'success' => true,
                'message' => 'تم تفعيل الترخيص بنجاح',
                'license' => $license
            ];

        } catch (Exception $e) {
            Log::error('License activation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تفعيل الترخيص: ' . $e->getMessage()
            ];
        }
    }

    /**
     * التحقق من صلاحية الترخيص
     */
    public static function verify(): bool
    {
        if (!config('license.enabled')) {
            return true;
        }

        $license = self::getCurrentLicense();

        if (!$license) {
            return false;
        }

        // تحديث آخر تحقق (باستخدام Connection الافتراضي)
        // إذا فشل، نتابع بدون تحديث (لعدم إيقاف النظام)
        try {
            // استخدام Connection الافتراضي (سيتم تبديله تلقائياً إلى SQLite في Local)
            $license->update(['last_verified_at' => now()]);
        } catch (\Exception $e) {
            // في حالة فشل التحديث (مثلاً MySQL غير متاح)، جرب SQLite
            Log::debug('Failed to update license in default connection, trying SQLite', [
                'error' => $e->getMessage()
            ]);
            
            try {
                // محاولة التحديث في SQLite مباشرة
                $sqliteLicense = License::on('sync_sqlite')
                    ->where('id', $license->id)
                    ->first();
                
                if ($sqliteLicense) {
                    $sqliteLicense->update(['last_verified_at' => now()]);
                }
            } catch (\Exception $e2) {
                // إذا فشل أيضاً، نتابع بدون تحديث last_verified_at
                // لا نوقف النظام بسبب فشل تحديث timestamp
                Log::debug('Failed to update license in SQLite, continuing without update', [
                    'error' => $e2->getMessage()
                ]);
            }
        }

        return $license->isValid();
    }

    /**
     * الحصول على Domain الحالي
     */
    public static function getCurrentDomain(): string
    {
        $domain = request()->getHost();
        
        // إزالة www إذا كان موجوداً
        $domain = preg_replace('/^www\./', '', $domain);
        
        return $domain;
    }

    /**
     * الحصول على Server Fingerprint
     */
    public static function getServerFingerprint(): string
    {
        $components = [
            php_uname('n'), // Hostname
            php_uname('m'), // Machine type
            PHP_OS, // Operating system
            $_SERVER['SERVER_SOFTWARE'] ?? '',
        ];

        // محاولة الحصول على MAC Address (إن أمكن)
        if (function_exists('exec') && !in_array('exec', explode(',', ini_get('disable_functions')))) {
            $macAddress = self::getMacAddress();
            if ($macAddress) {
                $components[] = $macAddress;
            }
        }

        return hash('sha256', implode('|', $components));
    }

    /**
     * الحصول على MAC Address
     */
    private static function getMacAddress(): ?string
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $output = [];
            exec('getmac /FO CSV /NH', $output);
            if (!empty($output[0])) {
                $parts = str_getcsv($output[0]);
                return $parts[0] ?? null;
            }
        } else {
            // Linux/Unix
            $output = [];
            exec("cat /sys/class/net/$(ip route show default | awk '/default/ {print $5}')/address 2>/dev/null", $output);
            if (!empty($output[0])) {
                return trim($output[0]);
            }
        }

        return null;
    }

    /**
     * تشفير مفتاح الترخيص
     */
    public static function encryptLicenseKey(array $data): string
    {
        $json = json_encode($data);
        $signature = hash_hmac('sha256', $json, config('license.secret_key'));
        
        $licenseData = [
            'data' => $data,
            'signature' => $signature,
            'created_at' => now()->toDateTimeString()
        ];

        return Crypt::encryptString(json_encode($licenseData));
    }

    /**
     * فك تشفير مفتاح الترخيص
     */
    public static function decryptLicenseKey(string $encryptedKey): ?array
    {
        try {
            $decrypted = Crypt::decryptString($encryptedKey);
            $licenseData = json_decode($decrypted, true);

            if (!isset($licenseData['data']) || !isset($licenseData['signature'])) {
                return null;
            }

            // التحقق من التوقيع
            $json = json_encode($licenseData['data']);
            $expectedSignature = hash_hmac('sha256', $json, config('license.secret_key'));

            if (!hash_equals($licenseData['signature'], $expectedSignature)) {
                Log::warning('License key signature verification failed');
                return null;
            }

            return $licenseData['data'];

        } catch (Exception $e) {
            Log::error('Failed to decrypt license key', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * حفظ الترخيص في ملف
     */
    private static function saveToFile(License $license): bool
    {
        try {
            $licenseFile = config('license.license_file');
            $data = [
                'license_key' => $license->license_key,
                'domain' => $license->domain,
                'fingerprint' => $license->fingerprint,
                'type' => $license->type,
                'activated_at' => $license->activated_at?->toDateTimeString(),
                'expires_at' => $license->expires_at?->toDateTimeString(),
                'saved_at' => now()->toDateTimeString(),
            ];

            File::put($licenseFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return true;

        } catch (Exception $e) {
            Log::error('Failed to save license to file', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * تحميل الترخيص من الملف
     */
    private static function loadFromFile(): ?License
    {
        try {
            $licenseFile = config('license.license_file');

            if (!File::exists($licenseFile)) {
                return null;
            }

            $data = json_decode(File::get($licenseFile), true);

            if (!$data) {
                return null;
            }

            // البحث في Database
            $license = License::where('domain', $data['domain'] ?? self::getCurrentDomain())
                ->first();

            if ($license) {
                return $license;
            }

            // إنشاء جديد من الملف
            return License::create([
                'license_key' => $data['license_key'] ?? null,
                'domain' => $data['domain'] ?? self::getCurrentDomain(),
                'fingerprint' => $data['fingerprint'] ?? self::getServerFingerprint(),
                'type' => $data['type'] ?? 'standard',
                'activated_at' => isset($data['activated_at']) ? $data['activated_at'] : now(),
                'expires_at' => $data['expires_at'] ?? null,
                'is_active' => true,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to load license from file', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * إلغاء تفعيل الترخيص
     */
    public static function deactivate(): bool
    {
        try {
            $license = self::getCurrentLicense();

            if ($license) {
                $license->update(['is_active' => false]);
                Cache::forget('current_license');
                
                // حذف الملف
                $licenseFile = config('license.license_file');
                if (File::exists($licenseFile)) {
                    File::delete($licenseFile);
                }

                return true;
            }

            return false;

        } catch (Exception $e) {
            Log::error('Failed to deactivate license', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * الحصول على معلومات الترخيص
     */
    public static function getLicenseInfo(): array
    {
        $license = self::getCurrentLicense();

        if (!$license) {
            return [
                'activated' => false,
                'message' => 'الترخيص غير مفعل'
            ];
        }

        return [
            'activated' => true,
            'valid' => $license->isValid(),
            'type' => $license->type,
            'expires_at' => $license->expires_at?->format('Y-m-d H:i:s'),
            'days_remaining' => $license->days_remaining,
            'domain' => $license->domain,
            'activated_at' => $license->activated_at?->format('Y-m-d H:i:s'),
            'last_verified_at' => $license->last_verified_at?->format('Y-m-d H:i:s'),
        ];
    }
}

