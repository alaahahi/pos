<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Services\LogService;

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
        'price_cost',
        'quantity',
        'price',
        'currency',
        'balen',
        'notes',
        'image',
        'created',
        'barcode',
        'is_active',
    ];

    /**
     * Accessor for image URL.
     */
    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        return isset($this->attributes['image']) && $this->attributes['image'] 
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

    /**
     * Boot method to add model events
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($product) {
            LogService::logProductCreated($product);
        });

        static::updated(function ($product) {
            LogService::logProductUpdated($product, $product->getOriginal());
        });

        static::deleted(function ($product) {
            LogService::logProductDeleted($product);
        });
    }
}
