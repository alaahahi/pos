<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SimpleDecorationOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'decoration_name',
        'customer_name',
        'customer_phone',
        'event_date',
        'total_price',
        'paid_amount',
        'assigned_employee_id',
        'special_requests',
        'status',
        'currency'
    ];

    protected $casts = [
        'event_date' => 'date',
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    // Relationships
    public function assignedEmployee()
    {
        return $this->belongsTo(User::class, 'assigned_employee_id');
    }

    // Accessors
    public function getRemainingAmountAttribute()
    {
        return $this->total_price - $this->paid_amount;
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'created' => '📝 تم الإنشاء',
            'received' => '📥 تم الاستلام',
            'executing' => '⚙️ قيد التنفيذ',
            'partial_payment' => '💰 دفعة جزئية',
            'full_payment' => '💵 دفعة كاملة',
            'completed' => '✅ مكتمل',
            'cancelled' => '❌ ملغي',
        ];

        return $labels[$this->status] ?? $this->status;
    }
}
