<?php

namespace App\Support;

class ShareMeta
{
    protected const DEFAULT_TAGLINE = 'تخطيط وتنسيق احتفالات صغيرة - حفلات استقبال المولود - أعياد ميلاد - كشف جنس المولود';

    /**
     * Build share meta from the Inertia page payload (server-rendered for crawlers).
     */
    public static function fromInertiaPage(array $page): array
    {
        $props = $page['props'] ?? [];
        $component = (string) ($page['component'] ?? '');
        $url = url($page['url'] ?? '/');

        if (str_starts_with($component, 'Shop/')) {
            return self::forShop($props, $component, $url);
        }

        if (str_starts_with($component, 'Public/')) {
            return self::forPublic($props, $component, $url);
        }

        return self::defaults($url);
    }

    public static function defaults(?string $url = null): array
    {
        $name = env('COMPANY_NAME', config('app.name', 'WEDOO EVENTS'));
        $description = env('COMPANY_TAGLINE', self::DEFAULT_TAGLINE);

        return self::build(
            title: $name,
            description: $description,
            image: self::defaultImage(),
            url: $url ?? url('/'),
            type: 'website',
            siteName: $name,
        );
    }

    protected static function forShop(array $props, string $component, string $url): array
    {
        $shop = $props['shop'] ?? [];
        $company = (string) ($shop['company_name'] ?? env('COMPANY_NAME', 'المتجر'));

        if ($component === 'Shop/ProductShow' && !empty($props['product'])) {
            $product = $props['product'];
            $title = ($product['name'] ?? 'منتج') . ' | ' . $company;
            $description = self::plainText(
                $product['description'] ?? $shop['seo_description'] ?? $shop['tagline'] ?? $title,
                160
            );
            $image = $product['image_url']
                ?? ($product['images_urls'][0] ?? null)
                ?? self::shopImage($shop);

            return self::build($title, $description, $image, $url, 'product', $company);
        }

        $title = $shop['seo_title'] ?? null;
        if (!$title) {
            $tagline = trim((string) ($shop['tagline'] ?? ''));
            $title = $tagline !== '' ? "{$company} | {$tagline}" : $company;
        }

        $description = self::plainText(
            $shop['seo_description'] ?? $shop['tagline'] ?? self::DEFAULT_TAGLINE,
            200
        );

        return self::build($title, $description, self::shopImage($shop), $url, 'website', $company);
    }

    protected static function forPublic(array $props, string $component, string $url): array
    {
        $info = $props['companyInfo'] ?? [];
        $company = (string) ($info['name'] ?? env('COMPANY_NAME', 'WEDOO EVENTS'));

        if ($component === 'Public/DecorationDetail' && !empty($props['decoration'])) {
            $decoration = $props['decoration'];
            $title = ($decoration['name'] ?? 'ديكور') . ' | ' . $company;
            $description = self::plainText(
                $decoration['description'] ?? "تصميم ديكور من {$company}",
                160
            );
            $image = $decoration['image_url']
                ?? $decoration['thumbnail_url']
                ?? self::companyLogo($info);

            return self::build($title, $description, $image, $url, 'article', $company);
        }

        $title = "معرض الديكورات | {$company}";
        $description = self::plainText(
            env('COMPANY_TAGLINE', "تصفّح أحدث تصاميم الديكورات والاحتفالات من {$company}"),
            200
        );

        return self::build($title, $description, self::companyLogo($info), $url, 'website', $company);
    }

    protected static function build(
        string $title,
        string $description,
        ?string $image,
        string $url,
        string $type,
        string $siteName,
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'image' => $image ?: self::defaultImage(),
            'url' => $url,
            'type' => $type,
            'site_name' => $siteName,
        ];
    }

    protected static function shopImage(array $shop): ?string
    {
        if (!empty($shop['logo'])) {
            $bases = $shop['storageBases'] ?? [];
            $base = rtrim((string) ($bases[0] ?? asset('public/storage')), '/');
            $resolved = self::absoluteUrl($base . '/' . ltrim((string) $shop['logo'], '/'));
            if ($resolved) {
                return $resolved;
            }
        }

        if (!empty($shop['logo_fallback'])) {
            return self::absoluteUrl((string) $shop['logo_fallback']);
        }

        return self::defaultImage();
    }

    protected static function companyLogo(array $info): ?string
    {
        $logo = $info['logo'] ?? env('COMPANY_LOGO', 'dashboard-assets/img/logo.png');

        return self::absoluteUrl($logo);
    }

    public static function defaultImage(): ?string
    {
        $candidates = array_filter([
            env('COMPANY_OG_IMAGE'),
            env('COMPANY_LOGO'),
            'dashboard-assets/img/logo.png',
            'dashboard-assets/img/WEDOO  LOGO PNG.png',
            'dashboard-assets/img/apple-touch-icon.png',
            'dashboard-assets/img/favicon.png',
        ]);

        foreach ($candidates as $candidate) {
            $url = self::absoluteUrl($candidate);
            if ($url) {
                return $url;
            }
        }

        return null;
    }

    protected static function absoluteUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return self::encodeUri($path);
        }

        $path = ltrim($path, '/');

        if (str_ends_with(strtolower($path), '.webp')) {
            $pngPath = preg_replace('/\.webp$/i', '.png', $path);
            if ($pngPath && file_exists(public_path($pngPath))) {
                $path = $pngPath;
            }
        }

        if (!file_exists(public_path($path)) && !str_starts_with($path, 'storage/')) {
            $storagePath = storage_path('app/public/' . $path);
            if (file_exists($storagePath)) {
                return self::encodeUri(asset('public/storage/' . $path));
            }
        }

        return self::encodeUri(asset($path));
    }

    protected static function encodeUri(string $url): string
    {
        return str_replace(' ', '%20', $url);
    }

    protected static function plainText(?string $value, int $limit): string
    {
        $text = trim(strip_tags((string) $value));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        if (mb_strlen($text) <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
    }
}
