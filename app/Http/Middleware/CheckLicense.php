<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\LicenseService;
use Illuminate\Support\Facades\Route;

class CheckLicense
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // التحقق المشفر من تفعيل نظام الترخيص
        // هذا يمنع تعطيل النظام بسهولة من .env
        $isEnabled = $this->checkLicenseEnabled();
        
        // إذا كان نظام الترخيص معطلاً بشكل صحيح، السماح بالمرور
        // لكن فقط إذا لم يكن هناك ترخيص مفعل في قاعدة البيانات أو ملف
        // ⚠️ هذا يسمح بالمرور فقط عند التعطيل الصريح وعدم وجود ترخيص
        if (!$isEnabled) {
            // التحقق من وجود ترخيص مفعل في قاعدة البيانات
            $hasActiveLicense = LicenseService::getCurrentLicense();
            if (!$hasActiveLicense) {
                // التحقق من ملف الترخيص
                $licenseFile = config('license.license_file');
                if (!file_exists($licenseFile)) {
                    // لا يوجد ترخيص في قاعدة البيانات أو ملف، وال system معطل صراحة
                    // السماح بالمرور فقط للسماح بالتفعيل الأول
                    return $next($request);
                }
            }
            // إذا كان هناك ترخيص مفعل، يجب تفعيل النظام (لا نسمح بالتعطيل)
        }
        
        // التحقق من Routes المستثناة أولاً
        $routeName = Route::currentRouteName();
        $excludedRoutes = config('license.excluded_routes', []);

        if ($routeName && in_array($routeName, $excludedRoutes)) {
            return $next($request);
        }

        // التحقق من Controllers المستثناة
        $route = $request->route();
        if (!$route) {
            return $next($request);
        }
        
        // التحقق من أن Route يحتوي على Controller وليس Closure
        $action = $route->getAction();
        if (isset($action['controller'])) {
            $controller = $action['controller'];
            // إذا كان Controller هو Closure أو لا يحتوي على @، تخطي التحقق
            if ($controller instanceof \Closure || strpos($controller, '@') === false) {
                return $next($request);
            }
            
            // استخراج اسم Controller من "Controller@method"
            $controllerName = explode('@', $controller)[0];
            $controller = class_basename($controllerName);
        } else {
            // إذا لم يكن هناك controller في action، قد يكون Closure
            return $next($request);
        }
        
        $excludedControllers = config('license.excluded_controllers', []);

        if (in_array($controller, $excludedControllers)) {
            return $next($request);
        }

        // التحقق من الترخيص فقط إذا كان النظام مفعل فعلياً
        // إذا كان النظام معطل، نسمح بالمرور للسماح بالتفعيل الأول
        if ($isEnabled) {
            // النظام مفعل - يجب التحقق من الترخيص
            if (!LicenseService::isActivated()) {
                // إذا كان الطلب API، إرجاع JSON
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'الترخيص غير مفعل أو منتهي الصلاحية',
                        'error' => 'License not activated or expired'
                    ], 403);
                }

                // توجيه لصفحة التفعيل
                return redirect()->route('license.activate')
                    ->with('error', 'يجب تفعيل الترخيص أولاً');
            }
        } else {
            // النظام معطل - السماح بالمرور للسماح بالتفعيل الأول
            return $next($request);
        }

        // التحقق من صلاحية الترخيص (إذا كان مفعلاً)
        if (!LicenseService::verify()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'الترخيص منتهي الصلاحية',
                    'error' => 'License expired'
                ], 403);
            }

            return redirect()->route('license.activate')
                ->with('error', 'الترخيص منتهي الصلاحية');
        }

        return $next($request);
    }

    /**
     * التحقق المشفر من تفعيل نظام الترخيص
     * يمنع تعطيل النظام بسهولة
     */
    private function checkLicenseEnabled(): bool
    {
        // أولاً: التحقق من وجود ترخيص مفعل في قاعدة البيانات
        // إذا كان هناك ترخيص صالح، النظام يجب أن يكون مفعل دائماً
        $hasActiveLicense = LicenseService::getCurrentLicense();
        if ($hasActiveLicense && $hasActiveLicense->isValid()) {
            return true; // إذا كان هناك ترخيص صالح، النظام يجب أن يكون مفعل دائماً
        }
        
        // ثانياً: التحقق من ملف الترخيص
        // إذا كان هناك ملف ترخيص، النظام مفعل
        $licenseFile = config('license.license_file');
        if (file_exists($licenseFile)) {
            return true; // إذا كان هناك ملف ترخيص، النظام مفعل
        }
        
        // ثالثاً: التحقق من .env و config
        $envValue = env('LICENSE_ENABLED');
        $configValue = config('license.enabled', true);
        
        // ⚠️ القيمة الافتراضية: مفعل (true)
        // هذا يمنع تعطيل النظام بسهولة
        // إذا لم يتم تعيين القيمة في .env، النظام مفعل
        if ($envValue === null) {
            return true; // إذا لم يتم تعيين القيمة، النظام مفعل (القيمة الافتراضية)
        }
        
        // السماح بالتعطيل إذا كانت القيمة false صراحة في .env
        // وليس هناك ترخيص مفعل في قاعدة البيانات أو ملف
        // ⚠️ إذا كانت القيمة null أو true، النظام مفعل
        return $envValue !== false && $configValue !== false;
    }
}

