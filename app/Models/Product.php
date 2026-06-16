<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'category_id',
    ];

    /**
     * Accessor for image URL.
     */
    protected $appends = ['image_url'];

    public function getImageUrlAttribute(): ?string
    {
        // الحصول على لوغو الشركة من .env
        $companyLogo = env('COMPANY_LOGO', 'dashboard-assets/img/logo.png');
        
        // إذا كانت هناك صورة محفوظة
        if (isset($this->attributes['image']) && $this->attributes['image']) {
            $imagePath = $this->attributes['image'];
            
            // إذا كانت الصورة هي default_product.png (غير موجودة)
            if ($imagePath === 'products/default_product.png') {
                return asset($companyLogo);
            }
            
            // التحقق من وجود الملف فعلياً
            $fullPath = storage_path("app/public/{$imagePath}");
            if (file_exists($fullPath)) {
                return asset("/public/storage/{$imagePath}");
            }
            
            // إذا لم يوجد الملف، إرجاع لوغو الشركة
            return asset($companyLogo);
        }
        
        // إرجاع لوغو الشركة كصورة افتراضية
        return asset($companyLogo);
    }

    /**
     * Relationship with logs.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(\App\Models\Log::class, 'by_user_id');
    }

    /**
     * Relationship with category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Filters used on the products index / export (same rules as list).
     *
     * @param  array{search?: string|null, status?: string|null, stock?: string|null}  $filters
     */
    public function scopeIndexFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                return $q->where(function (Builder $sub) use ($search) {
                    $sub->where('products.name', 'LIKE', "%{$search}%")
                        ->orWhere('products.barcode', 'LIKE', "%{$search}%")
                        ->orWhere('products.model', 'LIKE', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, function (Builder $q, string $status) {
                if ($status === 'active') {
                    return $q->where('products.is_active', 1);
                }
                if ($status === 'inactive') {
                    return $q->where('products.is_active', 0);
                }

                return $q;
            })
            ->when($filters['stock'] ?? null, function (Builder $q, string $stock) {
                if ($stock === 'low') {
                    return $q->where('products.quantity', '<=', 5)->where('products.quantity', '>', 0);
                }
                if ($stock === 'out') {
                    return $q->where('products.quantity', 0);
                }
                if ($stock === 'available') {
                    return $q->where('products.quantity', '>', 5);
                }

                return $q;
            });
    }

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_best_selling' => 'boolean',
        'sales_count' => 'integer',
        'category_id' => 'string',
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
