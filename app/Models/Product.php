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
        'is_featured',
        'is_best_selling',
        'sales_count',
    ];

    /**
     * Accessor for image URL.
     */
    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        // إذا كانت هناك صورة محفوظة
        if (isset($this->attributes['image']) && $this->attributes['image']) {
            return asset("storage/{$this->attributes['image']}");
        }
        
        // إرجاع الصورة الافتراضية
        return asset('dashboard-assets/img/product-placeholder.svg');
    }

    /**
     * Relationship with logs.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(\App\Models\Log::class, 'by_user_id');
    }

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_best_selling' => 'boolean',
        'sales_count' => 'integer',
        'created_at' => 'date:Y-m-d',
    ];

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
