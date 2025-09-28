<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'product_id',
        'quantity',
        'cost_price',
        'sales_price',
        'total',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'cost_price' => 'decimal:2',
        'sales_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the purchase invoice that owns the item.
     */
    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    /**
     * Get the product for the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate total amount
     */
    public function calculateTotal(): float
    {
        return $this->quantity * $this->cost_price;
    }

    /**
     * Boot method to auto-calculate total
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total = $item->calculateTotal();
        });
    }
}