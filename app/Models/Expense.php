<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'amount',
        'currency',
        'category',
        'expense_date',
        'created_by',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the user who created the expense
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * Get available currencies
     */
    public static function getCurrencies(): array
    {
        return [
            'IQD' => 'دينار عراقي',
            'USD' => 'دولار أمريكي',
        ];
    }

    /**
     * Get expense categories
     */
    public static function getCategories(): array
    {
        return [
            'salaries' => 'الرواتب',
            'shop_expenses' => 'مصاريف المحل',
            'utilities' => 'المرافق',
            'maintenance' => 'الصيانة',
            'marketing' => 'التسويق',
            'other' => 'أخرى',
        ];
    }
}