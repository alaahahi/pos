<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    use HasFactory;

    protected $table = 'product_price_history';

    protected $fillable = [
        'product_id',
        'old_price',
        'new_price',
        'change_reason',
        'changed_by',
        'purchase_invoice_id',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
    ];

    /**
     * Get the product that owns the price history.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user who changed the price.
     */
    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Get the purchase invoice that triggered the price change.
     */
    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    /**
     * Calculate price change percentage
     */
    public function getChangePercentageAttribute(): float
    {
        if ($this->old_price == 0) {
            return 0;
        }
        
        return (($this->new_price - $this->old_price) / $this->old_price) * 100;
    }

    /**
     * Get price change direction
     */
    public function getChangeDirectionAttribute(): string
    {
        if ($this->new_price > $this->old_price) {
            return 'increase';
        } elseif ($this->new_price < $this->old_price) {
            return 'decrease';
        }
        
        return 'no_change';
    }

    /**
     * Scope for filtering by product
     */
    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope for filtering by change reason
     */
    public function scopeByReason($query, $reason)
    {
        return $query->where('change_reason', $reason);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}