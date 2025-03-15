<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',    // معرف العميل (العلاقة مع جدول العملاء)
        'balance_dollar', // الرصيد بالدولار
        'balance_dinar',  // الرصيد بالدينار
        'last_transaction_date', // تاريخ آخر معاملة
        'currency_preference',   // تفضيل العملة (دولار أو دينار)
    ];

    // العلاقة مع العملاء
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
