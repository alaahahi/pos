<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'module_name',
        'action',
        'badge',
        'affected_record_id',
        'original_data',
        'updated_data',
        'by_user_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'created_at' => 'date:Y-m-d - H:i',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'by_user_id'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Product::class,
            'affected_record_id'
        );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            \App\Models\Customer::class,
            'affected_record_id'
        );
    }
}