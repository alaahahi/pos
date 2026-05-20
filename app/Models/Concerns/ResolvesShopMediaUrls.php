<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

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

        if (!file_exists(storage_path('app/public/' . $path))) {
            return null;
        }

        if (file_exists(public_path('storage/' . $path))) {
            return asset('storage/' . $path);
        }

        if (file_exists(public_path('public/storage/' . $path))) {
            return asset('public/storage/' . $path);
        }

        return Storage::disk('public')->url($path);
    }
}