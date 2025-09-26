<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DecorationOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'decoration_id',
        'customer_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'event_address',
        'event_date',
        'event_time',
        'guest_count',
        'special_requests',
        'selected_items',
        'base_price',
        'additional_cost',
        'discount',
        'total_price',
        'currency',
        'status',
        'assigned_team_id',
        'assigned_employee_id',
        'notes',
        'paid_amount',
        'scheduled_date',
        'completed_at',
        'paid_at',
        'received_at',
        'executing_at',
        'partial_payment_at',
        'full_payment_at'
    ];

    protected $casts = [
        'selected_items' => 'array',
        'event_date' => 'datetime',
        'event_time' => 'datetime:H:i',
        'base_price' => 'decimal:2',
        'additional_cost' => 'decimal:2',
        'discount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'currency' => 'string',
        'scheduled_date' => 'datetime',
        'completed_at' => 'datetime',
        'paid_at' => 'datetime',
        'received_at' => 'datetime',
        'executing_at' => 'datetime',
        'partial_payment_at' => 'datetime',
        'full_payment_at' => 'datetime'
    ];

    // العلاقات
    public function decoration()
    {
        return $this->belongsTo(Decoration::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedTeam()
    {
        return $this->belongsTo(DecorationTeam::class, 'assigned_team_id');
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(User::class, 'assigned_employee_id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    // Accessors
    public function getStatusNameAttribute()
    {
        $statuses = [
            'created' => 'تم الإنشاء',
            'received' => 'تم الاستلام',
            'executing' => 'قيد التنفيذ',
            'partial_payment' => 'دفع جزئي',
            'full_payment' => 'دفع كامل',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'created' => 'secondary',
            'received' => 'info',
            'executing' => 'primary',
            'partial_payment' => 'warning',
            'full_payment' => 'success',
            'completed' => 'success',
            'cancelled' => 'danger'
        ];

        return $colors[$this->status] ?? 'secondary';
    }
}
