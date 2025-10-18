<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'decoration_order_id',
        'commission_rate_percent',
        'base_amount',
        'commission_amount',
        'currency',
        'status',
        'period_month',
        'paid_at',
        'paid_by',
        'meta',
    ];

    protected $casts = [
        'commission_rate_percent' => 'decimal:3',
        'base_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'meta' => 'array',
        'paid_at' => 'datetime',
        'period_month' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(DecorationOrder::class, 'decoration_order_id');
    }

    // Scopes
    public function scopeAccrued($query)
    {
        return $query->where('status', 'accrued');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        $date = sprintf('%04d-%02d-01', $year, $month);
        return $query->whereDate('period_month', $date);
    }
}
