<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopSetting extends Model
{
    protected $fillable = [
        'is_enabled',
        'company_name',
        'whatsapp',
        'phone_country_code',
        'default_currency',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
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
