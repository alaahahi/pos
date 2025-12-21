<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\LicenseService;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class LicenseController extends Controller
{
    /**
     * عرض صفحة التفعيل
     */
    public function showActivate()
    {
        $licenseInfo = LicenseService::getLicenseInfo();
        $serverInfo = [
            'domain' => LicenseService::getCurrentDomain(),
            'fingerprint' => LicenseService::getServerFingerprint(),
        ];

        return Inertia::render('License/Activate', [
            'license' => $licenseInfo,
            'server' => $serverInfo,
        ]);
    }

    /**
     * تفعيل الترخيص
     */
    public function activate(Request $request): JsonResponse
    {
        $request->validate([
            'license_key' => 'required|string',
            'domain' => 'nullable|string',
            'fingerprint' => 'nullable|string',
        ]);

        $result = LicenseService::activate(
            $request->license_key,
            $request->domain,
            $request->fingerprint
        );

        if ($result['success']) {
            return Response::json([
                'success' => true,
                'message' => $result['message'],
                'license' => $result['license'],
            ], 200);
        }

        return Response::json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }

    /**
     * عرض حالة الترخيص
     */
    public function status(): JsonResponse
    {
        $licenseInfo = LicenseService::getLicenseInfo();
        $serverInfo = [
            'domain' => LicenseService::getCurrentDomain(),
            'fingerprint' => LicenseService::getServerFingerprint(),
        ];

        return Response::json([
            'success' => true,
            'license' => $licenseInfo,
            'server' => $serverInfo,
        ], 200);
    }

    /**
     * صفحة حالة الترخيص (Inertia)
     */
    public function showStatus()
    {
        $licenseInfo = LicenseService::getLicenseInfo();
        $serverInfo = [
            'domain' => LicenseService::getCurrentDomain(),
            'fingerprint' => LicenseService::getServerFingerprint(),
        ];

        return Inertia::render('License/Status', [
            'license' => $licenseInfo,
            'server' => $serverInfo,
        ]);
    }

    /**
     * إلغاء تفعيل الترخيص
     */
    public function deactivate(): JsonResponse
    {
        $result = LicenseService::deactivate();

        if ($result) {
            return Response::json([
                'success' => true,
                'message' => 'تم إلغاء تفعيل الترخيص بنجاح',
            ], 200);
        }

        return Response::json([
            'success' => false,
            'message' => 'فشل إلغاء تفعيل الترخيص',
        ], 400);
    }

    /**
     * التحقق من الترخيص
     */
    public function verify(): JsonResponse
    {
        $isValid = LicenseService::verify();
        $licenseInfo = LicenseService::getLicenseInfo();

        return Response::json([
            'success' => $isValid,
            'valid' => $isValid,
            'license' => $licenseInfo,
        ], $isValid ? 200 : 403);
    }

    /**
     * الحصول على معلومات السيرفر (للتوليد Offline)
     */
    public function getServerInfo(): JsonResponse
    {
        return Response::json([
            'success' => true,
            'domain' => LicenseService::getCurrentDomain(),
            'fingerprint' => LicenseService::getServerFingerprint(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'php_version' => PHP_VERSION,
        ], 200);
    }

    /**
     * عرض صفحة توليد مفتاح الترخيص
     */
    public function showGenerate()
    {
        return Inertia::render('License/Generate');
    }

    /**
     * توليد مفتاح الترخيص
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'domain' => 'nullable|string',
            'fingerprint' => 'nullable|string',
            'type' => 'nullable|string|in:trial,standard,premium',
            'expires_at' => 'nullable|date|after:today',
            'max_installations' => 'nullable|integer|min:1',
        ]);

        try {
            $domain = $request->domain ?? LicenseService::getCurrentDomain();
            $fingerprint = $request->fingerprint ?? LicenseService::getServerFingerprint();
            $type = $request->type ?? 'standard';
            $expiresAt = $request->expires_at ? date('Y-m-d H:i:s', strtotime($request->expires_at)) : null;
            $maxInstallations = $request->max_installations ?? 1;

            // إنشاء بيانات الترخيص
            $licenseData = [
                'domain' => $domain,
                'fingerprint' => $fingerprint,
                'type' => $type,
                'expires_at' => $expiresAt,
                'max_installations' => $maxInstallations,
                'generated_at' => now()->toDateTimeString(),
            ];

            // تشفير مفتاح الترخيص
            $licenseKey = LicenseService::encryptLicenseKey($licenseData);

            return Response::json([
                'success' => true,
                'message' => 'تم توليد مفتاح الترخيص بنجاح',
                'license_key' => $licenseKey,
                'license_data' => $licenseData,
            ], 200);

        } catch (\Exception $e) {
            return Response::json([
                'success' => false,
                'message' => 'حدث خطأ أثناء توليد مفتاح الترخيص: ' . $e->getMessage(),
            ], 500);
        }
    }
}

