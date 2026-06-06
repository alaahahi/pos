<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopOrder extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'order_number',
        'customer_phone',
        'customer_notes',
        'subtotal',
        'bundle_discount',
        'promotion_discount',
        'coupon_discount',
        'coupon_code',
        'total_amount',
        'currency',
        'totals_by_currency',
        'status',
        'whatsapp_sent_at',
        'ip_address',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'bundle_discount' => 'decimal:2',
        'promotion_discount' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'totals_by_currency' => 'array',
        'whatsapp_sent_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class, 'shop_order_id');
    }
}
