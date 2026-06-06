<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'company_name',
        'logo',
        'primary_color',
        'tagline',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'whatsapp',
        'phone_country_code',
        'default_currency',
        'exchange_rate',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'exchange_rate' => 'float',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'is_enabled' => true,
                'company_name' => env('COMPANY_NAME', 'المتجر'),
                'whatsapp' => env('COMPANY_PHONE', ''),
                'phone_country_code' => '964',
                'default_currency' => env('DEFAULT_CURRENCY', 'USD'),
            ]
        );
    }
}
