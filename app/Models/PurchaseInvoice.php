<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'total_amount',
        'invoice_date',
        'notes',
        'invoice_number',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Get the supplier that owns the purchase invoice.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'supplier_id');
    }

    /**
     * Get the user who created the purchase invoice.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the purchase invoice items for the purchase invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceItem::class);
    }

    /**
     * Get the product price histories for the purchase invoice.
     */
    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }

    /**
     * Generate invoice number
     */
    public static function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "PI{$year}{$month}";
        
        $lastInvoice = self::where('invoice_number', 'like', "{$prefix}%")
            ->orderBy('invoice_number', 'desc')
            ->first();
        
        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate total amount from items
     */
    public function calculateTotal(): float
    {
        return $this->items()->sum('total');
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by supplier
     */
    public function scopeBySupplier($query, $supplierId)
    {
        return $query->where('supplier_id', $supplierId);
    }
}