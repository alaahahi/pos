<?php

namespace App\Services;

use App\Models\ShopCartPromotion;
use App\Models\ShopCategory;
use App\Models\ShopCoupon;
use App\Models\ShopProduct;
use App\Models\ShopSetting;
use Illuminate\Support\Collection;

class ShopPricingService
{
    public function calculate(array $items, ?string $couponCode = null): array
    {
        $lines = $this->buildLines($items);
        $settings = ShopSetting::current();
        $defaultCurrency = $settings->default_currency ?: 'USD';
        $exchangeRate = $this->exchangeRate($settings);

        $totalsByCurrency = [];
        $couponContext = null;
        $promotionContext = null;

        $byCurrency = $lines->groupBy('currency');
        $currencyBuckets = [];

        foreach ($byCurrency as $currency => $currencyLines) {
            $cur = (string) $currency ?: $defaultCurrency;

            $subtotal = round($currencyLines->sum('line_total'), 2);
            $bundleDiscount = round($this->calculateBundleDiscount($currencyLines, $cur), 2);
            $subtotalAfterBundles = round(max(0, $subtotal - $bundleDiscount), 2);

            $currencyBuckets[$cur] = [
                'subtotal' => $subtotal,
                'bundle_discount' => $bundleDiscount,
                'subtotal_after_bundles' => $subtotalAfterBundles,
            ];
        }

        $promotionDiscounts = $this->resolvePromotionDiscounts($currencyBuckets, $exchangeRate);

        foreach ($currencyBuckets as $cur => $bucket) {
            $promotionDiscount = round($promotionDiscounts['amounts'][$cur] ?? 0, 2);

            $baseForCoupon = round(max(0, $bucket['subtotal_after_bundles'] - $promotionDiscount), 2);
            $couponResult = $this->resolveCoupon($couponCode, $baseForCoupon);
            $couponDiscount = $couponResult['amount'];

            $total = round(max(0, $baseForCoupon - $couponDiscount), 2);

            $totalsByCurrency[$cur] = [
                'subtotal' => $bucket['subtotal'],
                'bundle_discount' => $bucket['bundle_discount'],
                'subtotal_after_bundles' => $bucket['subtotal_after_bundles'],
                'promotion_discount' => $promotionDiscount,
                'coupon_discount' => $couponDiscount,
                'total' => $total,
                'currency' => $cur,
            ];

            if ($promotionDiscounts['promotion'] && $promotionContext === null) {
                $promotionContext = $promotionDiscounts['promotion'];
            }
            if ($couponResult['coupon'] && $couponContext === null) {
                $couponContext = $couponResult;
            } elseif ($couponResult['message'] && $couponContext === null) {
                $couponContext = $couponResult;
            }
        }

        $totalsByCurrency = array_filter(
            $totalsByCurrency,
            fn ($t) => ($t['total'] ?? 0) > 0 || ($t['subtotal'] ?? 0) > 0
        );

        $primary = $this->pickPrimaryCurrency($totalsByCurrency, $defaultCurrency);
        $primaryTotals = $totalsByCurrency[$primary] ?? [
            'subtotal' => 0,
            'bundle_discount' => 0,
            'subtotal_after_bundles' => 0,
            'promotion_discount' => 0,
            'coupon_discount' => 0,
            'total' => 0,
        ];

        return [
            'lines' => $lines->values()->all(),
            'totals_by_currency' => $totalsByCurrency,
            'currency' => $primary,
            'subtotal' => $primaryTotals['subtotal'],
            'bundle_discount' => $primaryTotals['bundle_discount'],
            'subtotal_after_bundles' => $primaryTotals['subtotal_after_bundles'],
            'promotion_discount' => $primaryTotals['promotion_discount'],
            'promotion' => $promotionContext,
            'coupon_discount' => $primaryTotals['coupon_discount'],
            'coupon' => $couponContext['coupon'] ?? null,
            'coupon_code' => $couponContext['code'] ?? null,
            'coupon_message' => $couponContext['message'] ?? null,
            'total' => $primaryTotals['total'],
            'exchange_rate' => $exchangeRate,
        ];
    }

    protected function buildLines(array $items): Collection
    {
        $defaultCurrency = ShopSetting::current()->default_currency ?: 'USD';
        $lines = collect();
        foreach ($items as $item) {
            $product = ShopProduct::with('category')->active()->categorized()->find($item['shop_product_id'] ?? null);
            if (!$product) {
                continue;
            }
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $withAddon = !empty($item['with_addon']);
            if ($withAddon && !$product->has_addon) {
                $withAddon = false;
            }
            $unitPrice = $product->unitPriceWithAddon($withAddon);
            $lines->push([
                'shop_product_id' => $product->id,
                'shop_category_id' => $product->shop_category_id,
                'product_name' => $product->name,
                'category_name' => $product->category?->name,
                'with_addon' => $withAddon,
                'addon_name' => $withAddon ? $product->addon_name : null,
                'addon_price' => $withAddon ? (float) $product->addon_price : null,
                'currency' => $product->currency ?: $defaultCurrency,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'line_total' => round($unitPrice * $qty, 2),
            ]);
        }
        return $lines;
    }

    protected function calculateBundleDiscount(Collection $currencyLines, string $currency): float
    {
        $discount = 0;
        $byCategory = $currencyLines->groupBy('shop_category_id');

        foreach ($byCategory as $categoryId => $categoryLines) {
            if (!$categoryId) {
                continue;
            }
            $category = ShopCategory::find($categoryId);
            if (!$category || !$category->bundle_quantity || !$category->bundle_price) {
                continue;
            }

            $bundleCurrency = $category->bundle_currency ?: $currency;
            if ($bundleCurrency !== $currency) {
                continue;
            }

            $totalQty = $categoryLines->sum('quantity');
            $fullPrice = $categoryLines->sum('line_total');
            if ($totalQty < $category->bundle_quantity) {
                continue;
            }

            $sets = intdiv($totalQty, $category->bundle_quantity);
            $remainder = $totalQty % $category->bundle_quantity;
            $avgPrice = $totalQty > 0 ? $fullPrice / $totalQty : 0;
            $bundledPrice = ($sets * (float) $category->bundle_price) + ($remainder * $avgPrice);
            $discount += max(0, $fullPrice - $bundledPrice);
        }

        return $discount;
    }

    /**
     * @param  array<string, array{subtotal: float, bundle_discount: float, subtotal_after_bundles: float}>  $currencyBuckets
     * @return array{amounts: array<string, float>, promotion: ?ShopCartPromotion}
     */
    protected function resolvePromotionDiscounts(array $currencyBuckets, ?float $exchangeRate): array
    {
        $amounts = array_fill_keys(array_keys($currencyBuckets), 0.0);

        if (empty($currencyBuckets)) {
            return ['amounts' => $amounts, 'promotion' => null];
        }

        if ($exchangeRate && count($currencyBuckets) > 1) {
            $combinedUsd = $this->combinedUsdTotal($currencyBuckets, $exchangeRate);
            $promotion = $this->findPromotion($combinedUsd);

            if (!$promotion) {
                return ['amounts' => $amounts, 'promotion' => null];
            }

            return [
                'amounts' => $this->distributePromotionDiscount($promotion, $currencyBuckets, $exchangeRate),
                'promotion' => $promotion,
            ];
        }

        $matchedPromotion = null;

        foreach ($currencyBuckets as $cur => $bucket) {
            $checkTotal = $exchangeRate
                ? $this->toUsd($bucket['subtotal_after_bundles'], $cur, $exchangeRate)
                : $bucket['subtotal_after_bundles'];

            $promotion = $this->findPromotion($checkTotal);
            if (!$promotion) {
                continue;
            }

            $amounts[$cur] = $this->promotionDiscountForBucket(
                $promotion,
                $bucket['subtotal_after_bundles'],
                $cur,
                $exchangeRate
            );

            if ($matchedPromotion === null) {
                $matchedPromotion = $promotion;
            }
        }

        return ['amounts' => $amounts, 'promotion' => $matchedPromotion];
    }

    protected function findPromotion(float $cartTotalUsd): ?ShopCartPromotion
    {
        return ShopCartPromotion::active()
            ->where('min_cart_total', '<=', $cartTotalUsd)
            ->orderByDesc('min_cart_total')
            ->orderByDesc('sort_order')
            ->get()
            ->first(fn ($p) => $p->isValidNow());
    }

    /**
     * @param  array<string, array{subtotal: float, bundle_discount: float, subtotal_after_bundles: float}>  $currencyBuckets
     */
    protected function distributePromotionDiscount(
        ShopCartPromotion $promotion,
        array $currencyBuckets,
        float $exchangeRate
    ): array {
        $amounts = array_fill_keys(array_keys($currencyBuckets), 0.0);

        if ($promotion->discount_type === 'percent') {
            foreach ($currencyBuckets as $cur => $bucket) {
                $amounts[$cur] = $this->discountAmount(
                    'percent',
                    (float) $promotion->discount_value,
                    $bucket['subtotal_after_bundles'],
                    null
                );
            }

            return $amounts;
        }

        $usdBase = (float) ($currencyBuckets['USD']['subtotal_after_bundles'] ?? 0);
        $iqdBase = (float) ($currencyBuckets['IQD']['subtotal_after_bundles'] ?? 0);
        $iqdAsUsd = $iqdBase > 0 ? $iqdBase / $exchangeRate : 0;

        if ($usdBase >= $iqdAsUsd && $usdBase > 0) {
            $amounts['USD'] = $this->discountAmount(
                'fixed',
                (float) $promotion->discount_value,
                $usdBase,
                null
            );
        } elseif ($iqdBase > 0) {
            $fixedIqd = (float) $promotion->discount_value * $exchangeRate;
            $amounts['IQD'] = $this->discountAmount('fixed', $fixedIqd, $iqdBase, null);
        }

        return $amounts;
    }

    protected function promotionDiscountForBucket(
        ShopCartPromotion $promotion,
        float $subtotalAfterBundles,
        string $currency,
        ?float $exchangeRate
    ): float {
        if ($promotion->discount_type === 'percent') {
            return $this->discountAmount(
                'percent',
                (float) $promotion->discount_value,
                $subtotalAfterBundles,
                null
            );
        }

        if ($exchangeRate && $currency === 'IQD') {
            $fixedIqd = (float) $promotion->discount_value * $exchangeRate;

            return $this->discountAmount('fixed', $fixedIqd, $subtotalAfterBundles, null);
        }

        return $this->discountAmount(
            'fixed',
            (float) $promotion->discount_value,
            $subtotalAfterBundles,
            null
        );
    }

    /**
     * @param  array<string, array{subtotal: float, bundle_discount: float, subtotal_after_bundles: float}>  $currencyBuckets
     */
    protected function combinedUsdTotal(array $currencyBuckets, float $exchangeRate): float
    {
        $total = 0.0;
        foreach ($currencyBuckets as $cur => $bucket) {
            $total += $this->toUsd($bucket['subtotal_after_bundles'], $cur, $exchangeRate);
        }

        return round($total, 2);
    }

    protected function toUsd(float $amount, string $currency, float $exchangeRate): float
    {
        if ($currency === 'IQD') {
            return $exchangeRate > 0 ? round($amount / $exchangeRate, 2) : $amount;
        }

        return $amount;
    }

    protected function exchangeRate(ShopSetting $settings): ?float
    {
        $rate = (float) ($settings->exchange_rate ?? 0);

        return $rate > 0 ? $rate : null;
    }

    public function resolveCoupon(?string $code, float $subtotalAfterBundles): array
    {
        if (!$code) {
            return ['amount' => 0, 'coupon' => null, 'code' => null, 'message' => null];
        }

        $coupon = ShopCoupon::whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])->first();
        if (!$coupon || !$coupon->isValidNow()) {
            return ['amount' => 0, 'coupon' => null, 'code' => null, 'message' => 'كود الخصم غير صالح'];
        }

        if ($subtotalAfterBundles < (float) $coupon->min_cart_total) {
            return [
                'amount' => 0,
                'coupon' => null,
                'code' => null,
                'message' => 'الحد الأدنى للفاتورة ' . $coupon->min_cart_total,
            ];
        }

        $amount = $this->discountAmount(
            $coupon->discount_type,
            (float) $coupon->discount_value,
            $subtotalAfterBundles,
            $coupon->max_discount_amount ? (float) $coupon->max_discount_amount : null
        );

        return ['amount' => $amount, 'coupon' => $coupon, 'code' => $coupon->code, 'message' => null];
    }

    protected function discountAmount(string $type, float $value, float $base, ?float $max): float
    {
        if ($type === 'percent') {
            $amount = round($base * ($value / 100), 2);
            if ($max !== null) {
                $amount = min($amount, $max);
            }
            return $amount;
        }
        return min($value, $base);
    }

    protected function pickPrimaryCurrency(array $totalsByCurrency, string $defaultCurrency): string
    {
        if (empty($totalsByCurrency)) {
            return $defaultCurrency;
        }
        if (isset($totalsByCurrency[$defaultCurrency])) {
            return $defaultCurrency;
        }
        $top = null;
        $max = -1;
        foreach ($totalsByCurrency as $cur => $t) {
            $value = (float) ($t['total'] ?? 0);
            if ($value > $max) {
                $max = $value;
                $top = $cur;
            }
        }
        return $top ?: $defaultCurrency;
    }
}
