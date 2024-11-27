<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes, HasRoles;

    /**
     * Fillable attributes for mass assignment.
     */
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
        'note',
        'image',
        'created',
        'is_active',
    ];

    /**
     * Accessor for image URL.
     */
    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return $this->attributes['image'] 
            ? asset("storage/{$this->attributes['image']}") 
            : null;
    }

    /**
     * Relationship with logs.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(\App\Models\Log::class, 'by_user_id');
    }
}
