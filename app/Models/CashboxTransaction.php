<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CashboxTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'currency',
        'description',
        'reference_type',
        'reference_id',
        'user_id',
        'from_wallet_id',
        'to_wallet_id',
        'transaction_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * Get the user who made the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the wallet the transaction came from.
     */
    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    /**
     * Get the wallet the transaction went to.
     */
    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    /**
     * Get the reference model (polymorphic relationship).
     */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for filtering by transaction type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for filtering by currency
     */
    public function scopeByCurrency($query, $currency)
    {
        return $query->where('currency', $currency);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get transaction type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'deposit' => 'إيداع',
            'withdrawal' => 'سحب',
            default => $this->type
        };
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
}