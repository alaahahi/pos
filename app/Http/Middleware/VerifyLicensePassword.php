<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyLicensePassword
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
        $requiredPassword = config('license.activation_password', 'Alaa.hahe@1');
        
        // التحقق من password في query string أو request body
        $providedPassword = $request->query('password') ?? $request->input('password');

        // إذا لم يتم توفير password أو كان غير صحيح
        if (!$providedPassword || $providedPassword !== $requiredPassword) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'كلمة المرور غير صحيحة',
                    'error' => 'Invalid password'
                ], 403);
            }

            // إرجاع صفحة خطأ
            abort(403, 'كلمة المرور غير صحيحة. يجب توفير password صحيح في URL أو Body.');
        }

        return $next($request);
    }
}

