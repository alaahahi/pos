<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // تحديث last_activity_at للمستخدم المسجل دخول
        try {
            $user = $request->user();
            
            if ($user) {
                // تحديث فقط إذا مر أكثر من 30 ثانية من آخر تحديث (لتقليل عدد الاستعلامات)
                if (!$user->last_activity_at || 
                    ($user->last_activity_at instanceof \Carbon\Carbon && $user->last_activity_at->diffInSeconds(now()) > 30)) {
                    
                    // استخدام connection الصحيح بناءً على البيئة
                    $connection = $this->getUserConnection($user);
                    
                    \DB::connection($connection)
                        ->table('users')
                        ->where('id', $user->id)
                        ->update(['last_activity_at' => now()]);
                }
            }
        } catch (\Exception $e) {
            // تجاهل الأخطاء في تحديث النشاط
            \Log::warning('Failed to update user activity: ' . $e->getMessage());
        }
        
        return $next($request);
    }
    
    /**
     * الحصول على connection الصحيح للمستخدم
     */
    protected function getUserConnection($user)
    {
        // إذا كان Local، استخدم SQLite
        if ($this->isLocalMode()) {
            return 'sync_sqlite';
        }
        
        // استخدم الاتصال الافتراضي
        return config('database.default');
    }
    
    /**
     * التحقق من وضع Local
     */
    protected function isLocalMode(): bool
    {
        $host = request()->getHost();
        $localHosts = ['localhost', '127.0.0.1', '::1'];
        
        if (in_array($host, $localHosts)) {
            return true;
        }
        
        if (app()->environment('local')) {
            return true;
        }
        
        return false;
    }
}
