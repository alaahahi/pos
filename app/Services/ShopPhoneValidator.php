<?php

namespace App\Services;

class ShopPhoneValidator
{
    public static function normalize(string $phone): string
    {
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $latin = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $phone = str_replace($arabic, $latin, $phone);
        $phone = preg_replace('/[^\d+]/', '', $phone) ?? '';
        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }
        return $phone;
    }

    public static function validate(string $phone, ?string $countryCode = '964'): array
    {
        $normalized = self::normalize($phone);
        $digits = preg_replace('/\D/', '', $normalized) ?? '';

        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return ['valid' => false, 'normalized' => $digits, 'message' => 'رقم الهاتف غير صالح (10–15 رقم)'];
        }

        return ['valid' => true, 'normalized' => $digits, 'message' => null];
    }
}
