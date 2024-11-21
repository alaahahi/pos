<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class VerifyActiveSession
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if ($user && $user->session_id !== session()->getId()) {
            Auth::logout();
            return back()->withErrors([
                'is_active' => 'Your account is inactive. Please contact support.',
            ]);
        }
       

        return $next($request);
    }
}
