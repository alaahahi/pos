<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ShopCoupon extends Model
{
    protected $fillable = [
        'code',
        'min_cart_total',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'is_active',
        'usage_limit',
        'used_count',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'min_cart_total' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isValidNow(): bool
    {
        if (!$this->is_active) {
            return false;
        }
        $now = Carbon::now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }
        return true;
    }
}
