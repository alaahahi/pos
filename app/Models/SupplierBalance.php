<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierBalance extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'supplier_balances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'supplier_id',
        'balance_dollar',
        'balance_dinar',
        'last_transaction_date',
        'currency_preference',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'balance_dollar' => 'decimal:2',
        'balance_dinar' => 'decimal:2',
        'last_transaction_date' => 'date',
    ];

    /**
     * Get the supplier that owns the balance.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}

