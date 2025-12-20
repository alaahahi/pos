<?php

namespace App\Helpers;

use App\Services\LicenseService;

if (!function_exists('license')) {
    /**
     * Helper function للتحقق من الترخيص
     */
    function license(): bool
    {
        return LicenseService::isActivated();
    }
}

if (!function_exists('license_info')) {
    /**
     * Helper function للحصول على معلومات الترخيص
     */
    function license_info(): array
    {
        return LicenseService::getLicenseInfo();
    }
}

if (!function_exists('license_type')) {
    /**
     * Helper function للحصول على نوع الترخيص
     */
    function license_type(): ?string
    {
        $info = LicenseService::getLicenseInfo();
        return $info['type'] ?? null;
    }
}

if (!function_exists('license_expires_at')) {
    /**
     * Helper function للحصول على تاريخ انتهاء الترخيص
     */
    function license_expires_at(): ?string
    {
        $info = LicenseService::getLicenseInfo();
        return $info['expires_at'] ?? null;
    }
}

if (!function_exists('license_days_remaining')) {
    /**
     * Helper function للحصول على الأيام المتبقية
     */
    function license_days_remaining(): ?int
    {
        $info = LicenseService::getLicenseInfo();
        return $info['days_remaining'] ?? null;
    }
}

