<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Models\Order;
use App\Observers\PermissionObserver;
use App\Observers\RoleObserver;
use App\Observers\UserObserver;
use App\Observers\OrderObserver;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // تحديد الاتصال الافتراضي أولاً (قبل أي شيء آخر)
        $this->configureDatabaseConnection();
        
        User::observe(UserObserver::class);
        Role::observe(RoleObserver::class);
        Permission::observe(PermissionObserver::class);
        Order::observe(OrderObserver::class);
        App::setLocale(Session::get('locale', config('app.locale')));
    }
    
    /**
     * تحديد الاتصال الافتراضي للقاعدة البيانات
     * Local: SQLite (sync_sqlite) للأداء
     * Online: MySQL للمزامنة
     * Fallback: إذا كان MySQL غير متوفر، استخدم SQLite تلقائياً
     */
    protected function configureDatabaseConnection(): void
    {
        // التحقق من وضع Local أولاً (أسرع)
        $isLocal = $this->isLocalMode();
        
        // إذا كان Local، استخدم SQLite مباشرة (لا حاجة للتحقق من MySQL)
        if ($isLocal) {
            // الحصول على المسار من config أولاً
            $sqlitePath = config('database.connections.sync_sqlite.database');
            
            // التحقق من أن المسار مطلق (يبدأ بـ / على Unix أو يحتوي على :\ على Windows)
            $isAbsolute = str_starts_with($sqlitePath, '/') || 
                         (strlen($sqlitePath) >= 2 && preg_match('/^[A-Za-z]:[\/\\\\]/', $sqlitePath));
            
            if ($isAbsolute) {
                // المسار مطلق - تحقق من أنه داخل المشروع
                $basePath = base_path();
                $basePathNormalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $basePath);
                $sqlitePathNormalized = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $sqlitePath);
                
                // إذا كان المسار المطلق لا يبدأ بـ base_path، استخدم database_path() بدلاً منه
                if (!str_starts_with($sqlitePathNormalized, $basePathNormalized)) {
                    // المسار المطلق خارج المشروع أو مسار قديم - استخدم database_path() بدلاً منه
                    $sqlitePath = database_path('sync.sqlite');
                }
            } else {
                // المسار نسبي - استخدم base_path() أو database_path()
                if (str_contains($sqlitePath, 'database') || str_contains($sqlitePath, 'sync.sqlite')) {
                    // إذا كان المسار يحتوي على 'database' أو 'sync.sqlite'، استخدم database_path()
                    $sqlitePath = database_path('sync.sqlite');
                } else {
                    // خلاف ذلك، استخدم base_path()
                    $sqlitePath = base_path($sqlitePath);
                }
            }
            
            // تنظيف المسار من أي تكرار (مثل D:\pos\D:\pos\...)
            $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $sqlitePath);
            
            // إذا كان المسار يحتوي على تكرار (مثل D:\pos\D:\pos\...)
            if (preg_match('/^([A-Za-z]:[^' . preg_quote(DIRECTORY_SEPARATOR, '/') . ']+)' . preg_quote(DIRECTORY_SEPARATOR, '/') . '\1/', $normalizedPath, $matches)) {
                // استخراج الجزء الأول فقط (D:\pos)
                $root = $matches[1];
                $rest = preg_replace('/^' . preg_quote($root, '/') . '/', '', $normalizedPath);
                $normalizedPath = $root . $rest;
            }
            
            $sqlitePath = $normalizedPath;
            
            if (!file_exists($sqlitePath)) {
                $this->createSQLiteFileIfNeeded($sqlitePath);
            }
            
            // تحديث المسار في config
            Config::set('database.connections.sync_sqlite.database', $sqlitePath);
            Config::set('database.default', 'sync_sqlite');
            
            // إعداد Queue connection لاستخدام sync_sqlite أيضاً
            Config::set('queue.connections.database.connection', 'sync_sqlite');
            
            return;
        }
        
        // إذا كان Online، تحقق من توفر MySQL
        // إذا كان MySQL غير متوفر (Offline)، استخدم SQLite كـ fallback
        $mysqlAvailable = $this->isMySQLAvailable();
        
        if (!$mysqlAvailable) {
            // MySQL غير متوفر - استخدم SQLite كـ fallback
            $sqlitePath = config('database.connections.sync_sqlite.database');
            if (file_exists($sqlitePath)) {
                Config::set('database.default', 'sync_sqlite');
                Config::set('queue.connections.database.connection', 'sync_sqlite');
            } else {
                // إذا لم يكن SQLite موجوداً، أنشئه
                $this->createSQLiteFileIfNeeded($sqlitePath);
                Config::set('database.default', 'sync_sqlite');
                Config::set('queue.connections.database.connection', 'sync_sqlite');
            }
        } else {
            // إذا كان Online و MySQL متوفر، استخدم MySQL للـ Queue أيضاً
            Config::set('queue.connections.database.connection', 'mysql');
        }
        // إذا كان Online و MySQL متوفر، MySQL هو الافتراضي (لا حاجة لتغييره)
    }
    
    /**
     * التحقق من توفر MySQL
     * يستخدم timeout قصير جداً لتجنب إبطاء التطبيق
     */
    protected function isMySQLAvailable(): bool
    {
        // إذا كان Local، لا نحتاج للتحقق من MySQL (استخدم SQLite مباشرة)
        if ($this->isLocalMode()) {
            return false; // في Local، استخدم SQLite دائماً
        }
        
        try {
            // محاولة الاتصال بـ MySQL مع timeout قصير جداً (1 ثانية)
            $host = config('database.connections.mysql.host', '127.0.0.1');
            $port = config('database.connections.mysql.port', 3306);
            
            // استخدام socket connection للسرعة
            $socket = @fsockopen($host, $port, $errno, $errstr, 1); // timeout 1 ثانية فقط
            if ($socket) {
                fclose($socket);
                return true;
            }
            return false;
        } catch (\Exception $e) {
            // MySQL غير متوفر (Offline أو خطأ في الاتصال)
            return false;
        }
    }
    
    /**
     * إنشاء ملف SQLite إذا لم يكن موجوداً
     */
    protected function createSQLiteFileIfNeeded(string $sqlitePath): void
    {
        $dir = dirname($sqlitePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        if (!file_exists($sqlitePath)) {
            touch($sqlitePath);
            chmod($sqlitePath, 0666);
        }
    }
    
    /**
     * التحقق من وضع Local
     */
    protected function isLocalMode(): bool
    {
        // التحقق من URL
        $host = Request::getHost();
        $scheme = Request::getScheme();
        
        // Local إذا كان localhost أو 127.0.0.1
        $localHosts = ['localhost', '127.0.0.1', '::1'];
        
        // التحقق من أن الاتصال محلي
        if (in_array($host, $localHosts)) {
            return true;
        }
        
        // التحقق من متغير البيئة
        if (env('APP_ENV') === 'local') {
            return true;
        }
        
        // التحقق من APP_URL في .env
        $appUrl = env('APP_URL', '');
        if ($appUrl && (str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1'))) {
            return true;
        }
        
        return false;
    }
}
