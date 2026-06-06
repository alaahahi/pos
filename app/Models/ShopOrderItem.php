<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopOrderItem extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'shop_order_id',
        'shop_product_id',
        'product_name',
        'category_name',
        'with_addon',
        'addon_name',
        'addon_price',
        'currency',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'with_addon' => 'boolean',
        'unit_price' => 'decimal:2',
        'addon_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'quantity' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'shop_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }
}
