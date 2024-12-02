<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'total_amount',
        'status',
        'payment_method',
        'notes',
    ];

    protected static function booted()
    {
        static::created(function ($invoice) {
            // Log the invoice creation with detailed information
            Log::info('Invoice created', [
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'total_amount' => $invoice->total_amount,
                'status' => $invoice->status,
                // You can add more fields here depending on what you want to log
            ]);
            
            // Create a log record in the Log model
            \App\Models\Log::create([
                'module_name' => 'Invoice',
                'action' => 'Created',
                'badge' => 'success',
                'affected_record_id' => $invoice->id,
                'original_data' => json_encode($invoice->getOriginal()),
                'updated_data' => json_encode($invoice->getAttributes()),
                'by_user_id' => auth()->user()->id, // Assuming the user is authenticated
            ]);
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
                    ->withPivot('quantity', 'selling_price') // إضافة الأعمدة الإضافية
                    ->withTimestamps();
    }
}
