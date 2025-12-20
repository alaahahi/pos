<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class License extends Model
{
    use HasFactory;

    /**
     * استخدام Connection الافتراضي (يتم تبديله تلقائياً إلى SQLite في Local)
     * لا حاجة لتحديد connection - سيستخدم config('database.default')
     */
    // protected $connection = null; // null = يستخدم الافتراضي

    protected $fillable = [
        'license_key',
        'domain',
        'fingerprint',
        'type',
        'max_installations',
        'activated_at',
        'expires_at',
        'is_active',
        'last_verified_at',
        'notes',
    ];

    protected $casts = [
        'activated_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'max_installations' => 'integer',
    ];

    /**
     * التحقق من صلاحية الترخيص
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * التحقق من انتهاء الترخيص
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false; // ترخيص دائم
        }

        return $this->expires_at->isPast();
    }

    /**
     * الأيام المتبقية للترخيص
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null; // ترخيص دائم
        }

        return max(0, now()->diffInDays($this->expires_at, false));
    }

    /**
     * التحقق من نوع الترخيص
     */
    public function isTrial(): bool
    {
        return $this->type === 'trial';
    }

    public function isStandard(): bool
    {
        return $this->type === 'standard';
    }

    public function isPremium(): bool
    {
        return $this->type === 'premium';
    }
}

