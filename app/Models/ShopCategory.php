<?php

namespace App\Models;

use App\Models\Concerns\ResolvesShopMediaUrls;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ShopCategory extends Model
{
    use HasUuids, SoftDeletes, ResolvesShopMediaUrls;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'images',
        'sort_order',
        'is_active',
        'bundle_quantity',
        'bundle_price',
        'bundle_currency',
    ];

    protected $casts = [
        'images' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'bundle_quantity' => 'integer',
        'bundle_price' => 'decimal:2',
    ];

    protected $appends = ['image_url', 'images_urls'];

    protected static function booted(): void
    {
        static::creating(function (ShopCategory $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name) ?: Str::uuid()->toString();
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(ShopProduct::class, 'shop_category_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return $this->resolveShopMediaUrl($this->image);
        }

        $images = $this->images ?? [];
        if (empty($images[0])) {
            return null;
        }

        return $this->resolveShopMediaUrl($images[0]);
    }

    public function getImagesUrlsAttribute(): array
    {
        $images = $this->images ?? [];
        return array_values(array_filter(array_map(
            fn ($path) => $path ? $this->resolveShopMediaUrl($path) : null,
            $images
        )));
    }
}
