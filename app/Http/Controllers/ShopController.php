<?php

namespace App\Http\Controllers;

use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Models\ShopSetting;
use App\Services\ShopOrderService;
use App\Services\ShopPricingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $settings = ShopSetting::current();
        if (!$settings->is_enabled) {
            return Inertia::render('Shop/Disabled', [
                'companyName' => $settings->company_name,
            ]);
        }

        $query = ShopProduct::with('category')->active()->orderBy('sort_order')->orderBy('name');

        if ($request->category) {
            $query->where('shop_category_id', $request->category);
        }
        if ($request->search) {
            $q = $request->search;
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $categories = ShopCategory::where('is_active', true)
            ->withCount(['products as active_products_count' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Shop/Index', [
            'products' => $query->paginate(24)->withQueryString(),
            'categories' => $categories,
            'filters' => $request->only(['category', 'search']),
            'shop' => $this->shopMeta($settings),
        ]);
    }

    public function show(ShopProduct $shopProduct)
    {
        $settings = ShopSetting::current();
        if (!$settings->is_enabled || !$shopProduct->is_active) {
            abort(404);
        }

        $shopProduct->load('category');

        return Inertia::render('Shop/ProductShow', [
            'product' => $shopProduct,
            'shop' => $this->shopMeta($settings),
        ]);
    }

    public function calculate(Request $request, ShopPricingService $pricing)
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.shop_product_id' => 'required|uuid',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'coupon_code' => 'nullable|string|max:64',
        ]);

        return response()->json($pricing->calculate($request->items, $request->coupon_code));
    }

    public function validateCoupon(Request $request, ShopPricingService $pricing)
    {
        $request->validate([
            'coupon_code' => 'required|string|max:64',
            'items' => 'required|array|min:1',
            'items.*.shop_product_id' => 'required|uuid',
            'items.*.quantity' => 'required|integer|min:1|max:999',
        ]);

        $calc = $pricing->calculate($request->items);
        $base = max(0, $calc['subtotal_after_bundles'] - $calc['promotion_discount']);
        $coupon = $pricing->resolveCoupon($request->coupon_code, $base);

        return response()->json([
            'valid' => $coupon['amount'] > 0,
            'discount_type' => $coupon['coupon']?->discount_type,
            'discount_amount' => $coupon['amount'],
            'min_cart_total' => $coupon['coupon']?->min_cart_total,
            'message' => $coupon['message'] ?? ($coupon['amount'] > 0 ? 'تم تطبيق الكوبون' : 'كود غير صالح'),
        ]);
    }

    public function storeOrder(Request $request, ShopOrderService $orders)
    {
        $request->validate([
            'customer_phone' => 'required|string|max:32',
            'customer_notes' => 'nullable|string|max:1000',
            'coupon_code' => 'nullable|string|max:64',
            'items' => 'required|array|min:1',
            'items.*.shop_product_id' => 'required|uuid',
            'items.*.quantity' => 'required|integer|min:1|max:999',
        ]);

        try {
            $result = $orders->create($request->only([
                'customer_phone', 'customer_notes', 'coupon_code', 'items',
            ]), $request->ip());

            return response()->json([
                'success' => true,
                'order_id' => $result['order']->id,
                'order_number' => $result['order']->order_number,
                'whatsapp_url' => $result['whatsapp_url'],
                'total' => $result['order']->total_amount,
                'currency' => $result['order']->currency,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    protected function shopMeta(ShopSetting $settings): array
    {
        return [
            'company_name' => $settings->company_name ?? env('COMPANY_NAME', 'المتجر'),
            'whatsapp' => $settings->whatsapp,
            'currency' => $settings->default_currency,
        ];
    }
}
