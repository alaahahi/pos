<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // استخدام الاتصال الافتراضي (سيتم تحديده تلقائياً في AppServiceProvider)
        // إذا كان Local أو MySQL غير متوفر، سيستخدم SQLite
        try {
            $user = User::where('email', $request->email)->first();
        } catch (\Exception $e) {
            // إذا فشل الاتصال الافتراضي، جرب SQLite
            try {
                $user = User::on('sync_sqlite')->where('email', $request->email)->first();
            } catch (\Exception $e2) {
                // لا يوجد اتصال متوفر
                return back()->withErrors([
                    'email' => 'فشل الاتصال بقاعدة البيانات. يرجى المحاولة مرة أخرى.',
                ]);
            }
        }
    
        // Check if the user is inactive
        if ($user && !$user->is_active) {
            return back()->withErrors([
                'is_active' => 'Your account is inactive. Please contact support.',
            ]);
        }
    
        // Log out the previous session if it exists
        if ($user && $user->session_id) {
            \Session::getHandler()->destroy($user->session_id);
        }
    
        // Authenticate the user
        $request->authenticate();
        // Update the session ID for the current session
        // Regenerate the session to prevent session fixation
        $request->session()->regenerate();

        $user->session_id = session()->getId();
        $user->save();
    


        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $user->session_id = null;
        $user->save();
        
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
