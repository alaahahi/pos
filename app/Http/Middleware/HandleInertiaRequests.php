<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Route;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): string|null
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // جلب المستخدم مع fallback للاتصال الصحيح
        $user = $this->getUser($request);
        
        return [
            ...parent::share($request),
            'auth' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'notificationCount' => $this->getNotificationCount($user),
                'avatar' => $user->avatar,
            ] : null,
            'flash' => [
                'success' => $request->session()->get('success')
            ],
            'auth_permissions' => $user ? $this->getUserPermissions($user) : []

        ];
    }
    
    /**
     * جلب المستخدم مع fallback للاتصال الصحيح
     */
    protected function getUser(Request $request)
    {
        // إذا كان Local، استخدم SQLite مباشرة
        if ($this->isLocalMode()) {
            try {
                $userId = $this->getUserIdFromSession($request);
                if ($userId) {
                    return \App\Models\User::on('sync_sqlite')->find($userId);
                }
            } catch (\Exception $e) {
                // لا يوجد مستخدم
            }
            return null;
        }
        
        // إذا كان Online، استخدم الاتصال الافتراضي
        try {
            return $request->user();
        } catch (\Exception $e) {
            // إذا فشل، جرب SQLite كـ fallback
            try {
                $userId = $this->getUserIdFromSession($request);
                if ($userId) {
                    return \App\Models\User::on('sync_sqlite')->find($userId);
                }
            } catch (\Exception $e2) {
                // لا يوجد مستخدم
            }
            return null;
        }
    }
    
    /**
     * جلب user_id من session
     */
    protected function getUserIdFromSession(Request $request)
    {
        // من Auth guard
        if (\Auth::check()) {
            return \Auth::id();
        }
        
        // من session مباشرة
        $sessionData = $request->session()->all();
        foreach ($sessionData as $key => $value) {
            if (str_contains($key, 'login_web_') && is_numeric($value)) {
                return $value;
            }
        }
        
        return null;
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
    
    /**
     * جلب عدد الإشعارات
     */
    protected function getNotificationCount($user)
    {
        try {
            return $user->unreadNotifications()->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * جلب صلاحيات المستخدم
     */
    protected function getUserPermissions($user)
    {
        try {
            // في SQLite، قد تكون هناك مشكلة مع model_type
            // جرب getPermissionsViaRoles أولاً
            try {
                return $user->getPermissionsViaRoles()->pluck('name')->toArray();
            } catch (\Exception $e) {
                // إذا فشل، جرب getAllPermissions
                try {
                    return $user->getAllPermissions()->pluck('name')->toArray();
                } catch (\Exception $e2) {
                    // إذا فشل أيضاً، أرجع array فارغ
                    return [];
                }
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set the root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     */
    public function rootView(Request $request): string
    {
        return parent::rootView($request);
    }
}
