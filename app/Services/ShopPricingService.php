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
        $defaultCurrency = ShopSetting::current()->default_currency ?: 'USD';

        $totalsByCurrency = [];
        $couponContext = null;
        $promotionContext = null;

        $byCurrency = $lines->groupBy('currency');
        foreach ($byCurrency as $currency => $currencyLines) {
            $cur = (string) $currency ?: $defaultCurrency;

            $subtotal = round($currencyLines->sum('line_total'), 2);
            $bundleDiscount = round($this->calculateBundleDiscount($currencyLines, $cur), 2);
            $subtotalAfterBundles = round(max(0, $subtotal - $bundleDiscount), 2);

            $promotion = $this->resolvePromotion($subtotalAfterBundles);
            $promotionDiscount = $promotion['amount'];

            $baseForCoupon = round(max(0, $subtotalAfterBundles - $promotionDiscount), 2);
            $couponResult = $this->resolveCoupon($couponCode, $baseForCoupon);
            $couponDiscount = $couponResult['amount'];

            $total = round(max(0, $baseForCoupon - $couponDiscount), 2);

            $totalsByCurrency[$cur] = [
                'subtotal' => $subtotal,
                'bundle_discount' => $bundleDiscount,
                'subtotal_after_bundles' => $subtotalAfterBundles,
                'promotion_discount' => $promotionDiscount,
                'coupon_discount' => $couponDiscount,
                'total' => $total,
                'currency' => $cur,
            ];

            if ($promotion['promotion'] && $promotionContext === null) {
                $promotionContext = $promotion['promotion'];
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

    protected function resolvePromotion(float $subtotalAfterBundles): array
    {
        $promotion = ShopCartPromotion::active()
            ->where('min_cart_total', '<=', $subtotalAfterBundles)
            ->orderByDesc('min_cart_total')
            ->orderByDesc('sort_order')
            ->get()
            ->first(fn ($p) => $p->isValidNow());

        if (!$promotion) {
            return ['amount' => 0, 'promotion' => null];
        }

        return [
            'amount' => $this->discountAmount($promotion->discount_type, (float) $promotion->discount_value, $subtotalAfterBundles, null),
            'promotion' => $promotion,
        ];
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
