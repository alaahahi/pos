<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Box extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'amount',
        'type',
        'description',
        'is_pay',
        'morphed_id',
        'morphed_type',
        'currency',
        'created',
        'discount',
        'parent_id',
        'details'
    ];
    protected $casts = [
        'details' => 'array',
    ];

    protected $dates = ['deleted_at'];

    public function morphed()
    {
        return $this->morphTo('morphed', 'morphed_type', 'morphed_id');
    }
    public function TransactionsImages()
    {
        return $this->hasMany(TransactionsImages::class, 'transactions_id');
    }
}
