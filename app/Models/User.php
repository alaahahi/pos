<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,   HasRoles;
    
    /**
     * The table associated with the model.
     * اسم الجدول هو 'users' في كل من MySQL و SQLite
     */
    protected $table = 'users';
    
    /**
     * Get the database connection for the model.
     * في Local، استخدم SQLite تلقائياً
     */
    public function getConnectionName()
    {
        // إذا كان Local، استخدم SQLite
        if ($this->isLocalMode()) {
            return 'sync_sqlite';
        }
        
        // استخدم الاتصال الافتراضي
        return parent::getConnectionName();
    }
    
    /**
     * التحقق من وضع Local
     */
    protected function isLocalMode(): bool
    {
        // التحقق من URL
        $host = request()->getHost();
        $localHosts = ['localhost', '127.0.0.1', '::1'];
        
        if (in_array($host, $localHosts)) {
            return true;
        }
        
        // التحقق من متغير البيئة
        if (app()->environment('local')) {
            return true;
        }
        
        return false;
    }


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'password',
        'created_at',
        'is_active',
        'avatar',
        'updated_at',
        'commission_enabled',
        'commission_rate_percent',
        'last_activity_at'
    ];

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute()
    {
        if (isset($this->attributes['avatar']) && $this->attributes['avatar']) {
            return asset("storage/{$this->attributes['avatar']}");
        }
        return asset('storage/avatars/default_avatar.png');
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];



    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'created_at'  => 'date:Y-m-d',
        'last_activity_at' => 'datetime',
    ];

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
    public function morphed()
    {
        return $this->morphTo();
    }
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('d M Y');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(
            \App\Models\Log::class,
            'by_user_id'
        );
    }

    public function assignedDecorationOrders(): HasMany
    {
        return $this->hasMany(\App\Models\DecorationOrder::class, 'assigned_employee_id');
    }

}
