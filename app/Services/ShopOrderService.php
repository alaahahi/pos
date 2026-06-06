<?php

namespace App\Services;

use App\Models\ShopCoupon;
use App\Models\ShopOrder;
use App\Models\ShopOrderItem;
use App\Models\ShopSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ShopOrderService
{
    public function __construct(
        protected ShopPricingService $pricing,
        protected ShopWhatsAppService $whatsapp
    ) {}

    public function create(array $data, ?string $ip = null): array
    {
        $phoneResult = ShopPhoneValidator::validate($data['customer_phone'] ?? '');
        if (!$phoneResult['valid']) {
            throw new \InvalidArgumentException($phoneResult['message']);
        }

        $settings = ShopSetting::current();
        if (!$settings->is_enabled) {
            throw new \InvalidArgumentException('المتجر غير متاح حالياً');
        }

        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new \InvalidArgumentException('السلة فارغة');
        }

        $pricing = $this->pricing->calculate($items, $data['coupon_code'] ?? null);
        if (empty($pricing['lines'])) {
            throw new \InvalidArgumentException('لا توجد منتجات صالحة في السلة');
        }

        return DB::transaction(function () use ($data, $phoneResult, $pricing, $settings, $ip) {
            $primaryCurrency = $pricing['currency'] ?: ($settings->default_currency ?: 'USD');
            $totalsByCurrency = $pricing['totals_by_currency'] ?? [];

            $order = ShopOrder::create([
                'order_number' => $this->nextOrderNumber(),
                'customer_phone' => $phoneResult['normalized'],
                'customer_notes' => $data['customer_notes'] ?? null,
                'subtotal' => $pricing['subtotal'],
                'bundle_discount' => $pricing['bundle_discount'],
                'promotion_discount' => $pricing['promotion_discount'],
                'coupon_discount' => $pricing['coupon_discount'],
                'coupon_code' => $pricing['coupon_code'],
                'total_amount' => $pricing['total'],
                'currency' => $primaryCurrency,
                'totals_by_currency' => $totalsByCurrency ?: null,
                'status' => 'pending',
                'ip_address' => $ip,
            ]);

            foreach ($pricing['lines'] as $line) {
                ShopOrderItem::create([
                    'shop_order_id' => $order->id,
                    'shop_product_id' => $line['shop_product_id'],
                    'product_name' => $line['product_name'],
                    'category_name' => $line['category_name'] ?? null,
                    'with_addon' => $line['with_addon'] ?? false,
                    'addon_name' => $line['addon_name'] ?? null,
                    'addon_price' => $line['addon_price'] ?? null,
                    'currency' => $line['currency'] ?? $primaryCurrency,
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
            }

            if ($pricing['coupon'] instanceof ShopCoupon) {
                $pricing['coupon']->increment('used_count');
            }

            $order->load('items');
            $whatsappUrl = $this->whatsapp->buildUrl($order, $pricing);
            if ($whatsappUrl) {
                $order->update(['whatsapp_sent_at' => now()]);
            }

            return [
                'order' => $order,
                'pricing' => $pricing,
                'whatsapp_url' => $whatsappUrl,
            ];
        });
    }

    protected function nextOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $prefix = "SHOP-{$date}-";
        $last = ShopOrder::where('order_number', 'like', $prefix . '%')
            ->orderByDesc('order_number')
            ->value('order_number');

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
