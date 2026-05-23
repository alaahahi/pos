<?php

namespace App\Models;

use App\Models\Concerns\ResolvesShopMediaUrls;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ShopProduct extends Model
{
    use HasUuids, SoftDeletes, ResolvesShopMediaUrls;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'shop_category_id',
        'name',
        'slug',
        'description',
        'price',
        'addon_name',
        'addon_price',
        'currency',
        'image',
        'images',
        'video_url',
        'youtube_links',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'images' => 'array',
        'youtube_links' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'price' => 'decimal:2',
        'addon_price' => 'decimal:2',
    ];

    protected $appends = [
        'image_url',
        'images_urls',
        'has_addon',
    ];

    protected static function booted(): void
    {
        static::creating(function (ShopProduct $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name) ?: Str::uuid()->toString();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'shop_category_id');
    }

    public function getHasAddonAttribute(): bool
    {
        return filled($this->addon_name) && $this->addon_price !== null;
    }

    public function unitPriceWithAddon(bool $withAddon = false): float
    {
        $price = (float) $this->price;
        if ($withAddon && $this->has_addon) {
            $price += (float) $this->addon_price;
        }

        return $price;
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return $this->resolveMediaUrl($this->image);
        }

        $images = $this->images ?? [];
        if (!empty($images[0])) {
            return $this->resolveMediaUrl($images[0]);
        }

        return null;
    }

    public function getImagesUrlsAttribute(): array
    {
        $images = $this->images ?? [];
        $urls = array_map(fn ($path) => $this->resolveMediaUrl($path), $images);
        if ($this->image_url && !in_array($this->image_url, $urls, true)) {
            array_unshift($urls, $this->image_url);
        }
        return array_values(array_filter($urls));
    }

    protected function resolveMediaUrl(?string $path): ?string
    {
        return $this->resolveShopMediaUrl($path);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCategorized($query)
    {
        return $query->whereNotNull('shop_category_id');
    }
}
