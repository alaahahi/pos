<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Decoration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'base_price',
        'currency',
        'base_price_dollar',
        'base_price_dinar',
        'pricing_details',
        'included_items',
        'optional_items',
        'duration_hours',
        'team_size',
        'image',
        'images',
        'video_url',
        'youtube_links',
        'thumbnail',
        'is_active',
        'is_featured'
    ];

    protected $appends = [
        'image_url',
        'images_urls',
        'thumbnail_url',
        'video_url_attribute',
        'type_name',
        'current_price',
        'current_currency',
        'formatted_price'
    ];

    protected $casts = [
        'pricing_details' => 'array',
        'included_items' => 'array',
        'optional_items' => 'array',
        'images' => 'array',
        'youtube_links' => 'array',
        'base_price' => 'decimal:2',
        'base_price_dollar' => 'decimal:2',
        'base_price_dinar' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean'
    ];

    // العلاقات
    public function orders()
    {
        return $this->hasMany(DecorationOrder::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Accessors
    public function getImageUrlAttribute()
    {
        return $this->image ? asset("storage/{$this->image}") : null;
    }

    public function getImagesUrlsAttribute()
    {
        if (!$this->images) {
            return [];
        }
        
        return collect($this->images)->map(function ($image) {
            return asset("storage/{$image}");
        })->toArray();
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail ? asset("storage/{$this->thumbnail}") : $this->image_url;
    }

    public function getTypeNameAttribute()
    {
        $types = [
            'birthday' => 'عيد ميلاد',
            'gender_reveal' => 'تحديد جنس المولود',
            'baby_shower' => 'حفلة الولادة',
            'wedding' => 'زفاف',
            'graduation' => 'تخرج',
            'corporate' => 'شركات',
            'religious' => 'ديني'
        ];

        return $types[$this->type] ?? $this->type;
    }

    public function getCurrentPriceAttribute()
    {
        // Use base_price if available, otherwise fall back to currency-specific prices
        if ($this->base_price && $this->base_price > 0) {
            return $this->base_price;
        }
        
        // Fall back to currency-specific prices
        $currencyPrice = $this->currency === 'dollar' ? $this->base_price_dollar : $this->base_price_dinar;
        
        // If currency-specific price is also empty, try the other currency
        if (!$currencyPrice || $currencyPrice <= 0) {
            $currencyPrice = $this->currency === 'dollar' ? $this->base_price_dinar : $this->base_price_dollar;
        }
        
        // If still no price, return 0
        return $currencyPrice && $currencyPrice > 0 ? $currencyPrice : 0;
    }

    public function getCurrentCurrencyAttribute()
    {
        return $this->currency === 'dollar' ? 'دولار' : 'دينار';
    }

    public function getFormattedPriceAttribute()
    {
        $price = $this->current_price;
        $currency = $this->current_currency;
        
        // If price is 0 or null, show "اتصل للاستفسار"
        if (!$price || $price <= 0) {
            return 'اتصل للاستفسار';
        }
        
        return number_format($price, 2) . ' ' . $currency;
    }

    public function getVideoUrlAttributeAttribute()
    {
        // Return first YouTube link if available
        if ($this->youtube_links && is_array($this->youtube_links) && !empty($this->youtube_links)) {
            return $this->youtube_links[0];
        }
        
        // Fallback to video_url for backward compatibility
        if ($this->video_url && (strpos($this->video_url, 'youtube.com') !== false || strpos($this->video_url, 'youtu.be') !== false)) {
            return $this->video_url;
        }
        
        return $this->video_url;
    }
    
    public function getYoutubeLinksAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }
        return $value ?: [];
    }
}
