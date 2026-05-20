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
            $lines[] = "• {$item->product_name} × {$item->quantity} = {$item->line_total} {$order->currency}";
        }
        $lines[] = '';
        $lines[] = "المجموع: {$pricing['subtotal']} {$order->currency}";
        if ($pricing['bundle_discount'] > 0) {
            $lines[] = "خصم الحزم: -{$pricing['bundle_discount']}";
        }
        if ($pricing['promotion_discount'] > 0) {
            $lines[] = "خصم تلقائي: -{$pricing['promotion_discount']}";
        }
        if ($pricing['coupon_discount'] > 0) {
            $lines[] = "كوبون ({$order->coupon_code}): -{$pricing['coupon_discount']}";
        }
        $lines[] = "الإجمالي: {$order->total_amount} {$order->currency}";
        if ($order->customer_notes) {
            $lines[] = '';
            $lines[] = "ملاحظات: {$order->customer_notes}";
        }
        return implode("\n", $lines);
    }
}
