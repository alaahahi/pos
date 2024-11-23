<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes,HasRoles;

    protected $fillable = [
        'name',
        'model',
        'oe_number',
        'situation',
        'price_cost',
        'quantity',
        'price_with_transport',
        'selling_price',
        'balen',
        'notes',
        'created'
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(
            \App\Models\Log::class,
            'by_user_id'
        );
    }
}
