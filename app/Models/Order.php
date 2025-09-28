<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use App\Services\LogService;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'total_amount',
        'total_paid',
        'status',
        'payment_method',
        'notes',
        'date',
        'discount_amount',
        'discount_rate',
        'final_amount',
    ];

    protected static function booted()
    {
        static::created(function ($order) {
            LogService::logOrderCreated($order);
        });

        static::updated(function ($order) {
            LogService::logOrderUpdated($order, $order->getOriginal());
        });

        static::deleted(function ($order) {
            LogService::logOrderDeleted($order);
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // العلاقة Many-to-Many مع المنتجات
    public function products()
    {
        return $this->belongsToMany(Product::class)
                    ->withPivot('quantity', 'price') // إضافة الأعمدة الإضافية
                    ->withTimestamps();
    }
}
