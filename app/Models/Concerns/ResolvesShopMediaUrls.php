<?php

namespace App\Models\Concerns;

trait ResolvesShopMediaUrls
{
    protected function resolveShopMediaUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        foreach (['storage/', 'public/storage/'] as $prefix) {
            if (file_exists(public_path($prefix . $path))) {
                return asset($prefix . $path);
            }
        }

        return asset('storage/' . $path);
    }
}