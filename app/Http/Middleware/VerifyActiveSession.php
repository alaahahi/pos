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
            return response()->json(['message' => 'Session expired, please log in again.'], 401);
        }

        return $next($request);
    }
}
