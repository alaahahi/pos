<?php

namespace App\Services;

use App\Models\ShopOrder;
use App\Models\ShopSetting;

class ShopWhatsAppService
{
    public function buildUrl(ShopOrder $order, array $pricing): string
    {
        $settings = ShopSetting::current();
        $whatsapp = preg_replace('/\D/', '', $settings->whatsapp ?? '');
        if (!$whatsapp) {
            return '';
        }

        $text = $this->buildMessage($order, $pricing);
        return 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode($text);
    }

    public function buildMessage(ShopOrder $order, array $pricing): string
    {
        $lines = ["طلب متجر #{$order->order_number}", "الهاتف: {$order->customer_phone}", ''];

        foreach ($order->items as $item) {
            $label = $item->product_name;
            if ($item->with_addon && $item->addon_name) {
                $label .= " (+ {$item->addon_name})";
            }
            $cur = $item->currency ?: $order->currency;
            $lines[] = "• {$label} × {$item->quantity} = {$item->line_total} {$cur}";
        }
        $lines[] = '';

        $totals = $pricing['totals_by_currency'] ?? null;
        if (is_array($totals) && !empty($totals)) {
            $first = true;
            foreach ($totals as $cur => $t) {
                if (!$first) {
                    $lines[] = '';
                }
                $first = false;

                if (($t['subtotal'] ?? 0) > 0) {
                    $lines[] = "المجموع ({$cur}): {$t['subtotal']} {$cur}";
                }
                if (($t['bundle_discount'] ?? 0) > 0) {
                    $lines[] = "خصم الحزم ({$cur}): -{$t['bundle_discount']} {$cur}";
                }
                if (($t['promotion_discount'] ?? 0) > 0) {
                    $lines[] = "خصم تلقائي ({$cur}): -{$t['promotion_discount']} {$cur}";
                }
                if (($t['coupon_discount'] ?? 0) > 0) {
                    $couponCode = $order->coupon_code ? " ({$order->coupon_code})" : '';
                    $lines[] = "كوبون{$couponCode} ({$cur}): -{$t['coupon_discount']} {$cur}";
                }
                if (($t['total'] ?? 0) > 0) {
                    $lines[] = "الإجمالي ({$cur}): {$t['total']} {$cur}";
                }
            }
        } else {
            $lines[] = "المجموع: {$pricing['subtotal']} {$order->currency}";
            if (($pricing['bundle_discount'] ?? 0) > 0) {
                $lines[] = "خصم الحزم: -{$pricing['bundle_discount']}";
            }
            if (($pricing['promotion_discount'] ?? 0) > 0) {
                $lines[] = "خصم تلقائي: -{$pricing['promotion_discount']}";
            }
            if (($pricing['coupon_discount'] ?? 0) > 0) {
                $lines[] = "كوبون ({$order->coupon_code}): -{$pricing['coupon_discount']}";
            }
            $lines[] = "الإجمالي: {$order->total_amount} {$order->currency}";
        }

        if ($order->customer_notes) {
            $lines[] = '';
            $lines[] = "ملاحظات: {$order->customer_notes}";
        }
        return implode("\n", $lines);
    }
}
