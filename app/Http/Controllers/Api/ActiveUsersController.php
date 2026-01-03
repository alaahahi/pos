<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActiveUsersController extends Controller
{
    /**
     * Get active users (last activity within 3 minutes)
     */
    public function index(Request $request)
    {
        try {
            // المستخدمين النشطين (آخر تفاعل خلال 3 دقائق)
            $activeThreshold = Carbon::now()->subMinutes(3);
            
            // استخدام connection الصحيح
            $connection = $this->getConnection();
            
            $activeUsers = User::on($connection)
                ->where('is_active', true)
                ->where('last_activity_at', '>=', $activeThreshold)
                ->where('id', '!=', $request->user()->id) // استثناء المستخدم الحالي
                ->select('id', 'name', 'email', 'avatar', 'last_activity_at')
                ->orderBy('last_activity_at', 'desc')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar_url' => $user->avatar_url,
                        'last_activity_at' => $user->last_activity_at?->diffForHumans(),
                    ];
                });
            
            return response()->json([
                'active_users' => $activeUsers,
                'count' => $activeUsers->count(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching active users: ' . $e->getMessage());
            return response()->json([
                'active_users' => [],
                'count' => 0,
            ], 200);
        }
    }
    
    /**
     * الحصول على connection الصحيح
     */
    protected function getConnection()
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
