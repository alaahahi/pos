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

        if (file_exists(public_path('storage/' . $path))) {
            return asset('storage/' . $path);
        }

        return asset('public/storage/' . $path);
    }
}
