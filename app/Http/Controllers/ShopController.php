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

        $categories = ShopCategory::where('is_active', true)
            ->withCount(['products as active_products_count' => function ($q) {
                $q->where('is_active', true)->whereNotNull('shop_category_id');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $categoryId = $request->category;
        if ($categories->isNotEmpty()) {
            $validIds = $categories->pluck('id');
            if (!$categoryId || !$validIds->contains($categoryId)) {
                $categoryId = $categories->first()->id;
            }
        }

        $query = ShopProduct::with('category')->active()->categorized()->orderBy('sort_order')->orderBy('name');

        if ($categoryId) {
            $query->where('shop_category_id', $categoryId);
        }
        if ($request->search) {
            $q = $request->search;
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        return Inertia::render('Shop/Index', [
            'products' => $query->paginate(24)->withQueryString(),
            'categories' => $categories,
            'filters' => [
                'category' => $categoryId,
                'search' => $request->search,
            ],
            'shop' => $this->shopMeta($settings),
        ]);
    }

    public function show(ShopProduct $shopProduct)
    {
        $settings = ShopSetting::current();
        if (!$settings->is_enabled || !$shopProduct->is_active || !$shopProduct->shop_category_id) {
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
            'items.*.with_addon' => 'sometimes|boolean',
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
            'items.*.with_addon' => 'sometimes|boolean',
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
            'items.*.with_addon' => 'sometimes|boolean',
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
        $logoFallback = env('COMPANY_LOGO', 'dashboard-assets/img/logo.png');
        if ($logoFallback && !str_starts_with($logoFallback, 'http')) {
            $logoPath = ltrim($logoFallback, '/');
            if (str_ends_with(strtolower($logoPath), '.webp')) {
                $pngPath = preg_replace('/\.webp$/i', '.png', $logoPath);
                if ($pngPath && file_exists(public_path($pngPath))) {
                    $logoPath = $pngPath;
                }
            }
            $logoFallback = asset($logoPath);
        }

        return [
            'company_name' => $settings->company_name ?? env('COMPANY_NAME', 'المتجر'),
            'logo' => $settings->logo,
            'logo_fallback' => $logoFallback ?: null,
            'primary_color' => $settings->primary_color ?: '#4f46e5',
            'tagline' => $settings->tagline,
            'seo_title' => $settings->seo_title,
            'seo_description' => $settings->seo_description,
            'seo_keywords' => $settings->seo_keywords,
            'whatsapp' => $settings->whatsapp,
            'phone_country_code' => $settings->phone_country_code ?: '964',
            'currency' => $settings->default_currency,
            'exchange_rate' => $settings->exchange_rate,
            'storageBases' => [
                rtrim(asset('storage'), '/'),
                rtrim(asset('public/storage'), '/'),
            ],
        ];
    }
}
