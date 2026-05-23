<?php

namespace App\Http\Controllers;

use App\Models\ShopCartPromotion;
use App\Models\ShopCategory;
use App\Models\ShopCoupon;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\ShopSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShopSettingsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'general');
        $showTrashedProducts = $request->boolean('trashed');

        $productsQuery = ShopProduct::with('category')->orderBy('sort_order')->orderBy('name');
        if ($showTrashedProducts) {
            $productsQuery->onlyTrashed();
        }

        return Inertia::render('ShopSettings/Index', [
            'translations' => __('messages'),
            'activeTab' => $tab,
            'settings' => ShopSetting::current(),
            'showTrashedProducts' => $showTrashedProducts,
            'storageBases' => [
                rtrim(asset('storage'), '/'),
                rtrim(asset('public/storage'), '/'),
            ],
            'categories' => ShopCategory::orderBy('sort_order')->orderBy('name')->get(),
            'products' => $productsQuery->paginate(20)->withQueryString(),
            'promotions' => ShopCartPromotion::orderBy('sort_order')->get(),
            'coupons' => ShopCoupon::orderByDesc('id')->get(),
            'orders' => $tab === 'orders'
                ? $this->ordersQuery($request)->paginate(20)->withQueryString()
                : null,
            'orderFilters' => $request->only(['status', 'phone', 'date_from', 'date_to']),
        ]);
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->validate([
            'is_enabled' => 'boolean',
            'company_name' => 'nullable|string|max:255',
            'whatsapp' => 'nullable|string|max:32',
            'phone_country_code' => 'nullable|string|max:8',
            'default_currency' => 'nullable|string|max:8',
        ]);

        ShopSetting::current()->update($data);

        return back()->with('success', 'تم حفظ الإعدادات');
    }

    public function storeCategory(Request $request)
    {
        $data = $this->validateCategory($request);
        $data = $this->applyCategoryImageUpload($request, $data);
        ShopCategory::create($data);

        return redirect()
            ->route('shop-settings.index', ['tab' => 'categories'])
            ->with('success', 'تمت إضافة الفئة');
    }

    public function updateCategory(Request $request, ShopCategory $shopCategory)
    {
        $data = $this->validateCategory($request);
        $data = $this->applyCategoryImageUpload($request, $data, $shopCategory);
        $shopCategory->update($data);
        return back()->with('success', 'تم تحديث الفئة');
    }

    public function destroyCategory(ShopCategory $shopCategory)
    {
        $this->releaseShopCategorySlug($shopCategory);
        $this->deleteCategoryMedia($shopCategory);
        $shopCategory->delete();
        return back()->with('success', 'تم حذف الفئة');
    }

    public function storeProduct(Request $request)
    {
        $data = $this->validateProduct($request);
        unset($data['image']);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('shop/products', 'public');
        }
        $data['images'] = $this->storeUploadedImages($request, 'gallery', $data['images'] ?? []);
        ShopProduct::create($data);

        return redirect()
            ->route('shop-settings.index', ['tab' => 'products'])
            ->with('success', 'تمت إضافة المنتج');
    }

    public function updateProduct(Request $request, ShopProduct $shopProduct)
    {
        $data = $this->validateProduct($request, $shopProduct->id);
        unset($data['image']);
        if ($request->hasFile('image')) {
            if ($shopProduct->image) {
                Storage::disk('public')->delete($shopProduct->image);
            }
            $data['image'] = $request->file('image')->store('shop/products', 'public');
        }
        $data['images'] = $this->storeUploadedImages($request, 'gallery', $data['images'] ?? $shopProduct->images ?? []);
        $shopProduct->update($data);

        return redirect()
            ->route('shop-settings.index', ['tab' => 'products'])
            ->with('success', 'تم تحديث المنتج');
    }

    public function destroyProduct(ShopProduct $shopProduct)
    {
        $this->releaseShopProductSlug($shopProduct);
        $shopProduct->delete();

        return redirect()
            ->route('shop-settings.index', ['tab' => 'products'])
            ->with('success', 'تم حذف المنتج');
    }

    public function restoreProduct(string $shopProduct)
    {
        $product = ShopProduct::onlyTrashed()->findOrFail($shopProduct);

        $product->restore();
        $product->update([
            'slug' => $this->uniqueProductSlug($product->name, null, $product->id),
        ]);

        return redirect()
            ->route('shop-settings.index', ['tab' => 'products'])
            ->with('success', 'تم استعادة المنتج');
    }

    public function storePromotion(Request $request)
    {
        ShopCartPromotion::create($this->validatePromotion($request));
        return back()->with('success', 'تمت إضافة العرض');
    }

    public function updatePromotion(Request $request, ShopCartPromotion $promotion)
    {
        $promotion->update($this->validatePromotion($request));
        return back()->with('success', 'تم تحديث العرض');
    }

    public function destroyPromotion(ShopCartPromotion $promotion)
    {
        $promotion->delete();
        return back()->with('success', 'تم الحذف');
    }

    public function storeCoupon(Request $request)
    {
        $data = $this->validateCoupon($request);
        $data['code'] = strtoupper($data['code']);
        ShopCoupon::create($data);
        return back()->with('success', 'تمت إضافة الكوبون');
    }

    public function updateCoupon(Request $request, ShopCoupon $coupon)
    {
        $data = $this->validateCoupon($request);
        $data['code'] = strtoupper($data['code']);
        $coupon->update($data);
        return back()->with('success', 'تم تحديث الكوبون');
    }

    public function destroyCoupon(ShopCoupon $coupon)
    {
        $coupon->delete();
        return back()->with('success', 'تم الحذف');
    }

    public function showOrder(ShopOrder $shopOrder)
    {
        $shopOrder->load('items');
        return response()->json($shopOrder);
    }

    public function updateOrderStatus(Request $request, ShopOrder $shopOrder)
    {
        $request->validate(['status' => 'required|in:pending,confirmed,cancelled']);
        $shopOrder->update(['status' => $request->status]);
        return back()->with('success', 'تم تحديث الحالة');
    }

    public function exportOrders(Request $request): StreamedResponse
    {
        $orders = $this->ordersQuery($request)->with('items')->get();
        $filename = 'shop-orders-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, ['رقم الطلب', 'الهاتف', 'الحالة', 'المجموع', 'العملة', 'التاريخ', 'ملاحظات']);
            foreach ($orders as $order) {
                fputcsv($out, [
                    $order->order_number,
                    $order->customer_phone,
                    $order->status,
                    $order->total_amount,
                    $order->currency,
                    $order->created_at->format('Y-m-d H:i'),
                    $order->customer_notes,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function ordersQuery(Request $request)
    {
        $q = ShopOrder::with('items')->latest();
        if ($request->status) {
            $q->where('status', $request->status);
        }
        if ($request->phone) {
            $q->where('customer_phone', 'like', '%' . preg_replace('/\D/', '', $request->phone) . '%');
        }
        if ($request->date_from) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }
        return $q;
    }

    protected function validateCategory(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'bundle_quantity' => 'nullable|integer|min:2',
            'bundle_price' => 'nullable|numeric|min:0',
            'bundle_currency' => 'nullable|string|max:8',
            'image' => 'nullable|image|max:4096',
        ]);
        $data['slug'] = $this->uniqueCategorySlug(
            $data['name'],
            $data['slug'] ?? null,
            $request->route('shopCategory')?->id
        );
        unset($data['image']);

        return $data;
    }

    protected function uniqueCategorySlug(string $name, ?string $slug = null, ?string $exceptId = null): string
    {
        $base = $slug ?: Str::slug($name) ?: Str::uuid()->toString();
        $candidate = $base;
        $suffix = 1;

        while (
            ShopCategory::where('slug', $candidate)
                ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix++;
        }

        return $candidate;
    }

    protected function validateProduct(Request $request, ?string $exceptProductId = null): array
    {
        $data = $request->validate([
            'shop_category_id' => 'required|uuid|exists:shop_categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'video_url' => 'nullable|string|max:500',
            'youtube_links' => 'nullable|array',
            'youtube_links.*' => 'nullable|string|max:500',
            'images' => 'nullable|array',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:4096',
        ]);
        $data['slug'] = $this->uniqueProductSlug(
            $data['name'],
            $data['slug'] ?? null,
            $exceptProductId
        );
        $data['youtube_links'] = array_values(array_filter($data['youtube_links'] ?? []));
        return $data;
    }

    protected function uniqueProductSlug(string $name, ?string $slug = null, ?string $exceptId = null): string
    {
        $base = $slug ?: Str::slug($name) ?: Str::uuid()->toString();
        $candidate = $base;
        $suffix = 1;

        while (
            ShopProduct::withTrashed()
                ->where('slug', $candidate)
                ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix++;
        }

        return $candidate;
    }

    protected function releaseShopProductSlug(ShopProduct $product): void
    {
        $product->update([
            'slug' => $product->slug . '-deleted-' . Str::random(8),
        ]);
    }

    protected function releaseShopCategorySlug(ShopCategory $category): void
    {
        $category->update([
            'slug' => $category->slug . '-deleted-' . Str::random(8),
        ]);
    }

    protected function validatePromotion(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'min_cart_total' => 'required|numeric|min:0',
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);
    }

    protected function validateCoupon(Request $request): array
    {
        return $request->validate([
            'code' => 'required|string|max:64',
            'min_cart_total' => 'required|numeric|min:0',
            'discount_type' => 'required|in:fixed,percent',
            'discount_value' => 'required|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'usage_limit' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);
    }

    protected function applyCategoryImageUpload(Request $request, array $data, ?ShopCategory $shopCategory = null): array
    {
        if ($request->hasFile('image')) {
            if ($shopCategory) {
                $this->deleteCategoryMedia($shopCategory);
            }
            $path = $request->file('image')->store('shop/categories', 'public');
            $data['image'] = $path;
            $data['images'] = $this->storeUploadedImages(
                $request,
                'gallery',
                array_values(array_filter([$path]))
            );
        } else {
            $data['images'] = $this->storeUploadedImages(
                $request,
                'gallery',
                $shopCategory?->images ?? []
            );
            if ($shopCategory && !isset($data['image'])) {
                $data['image'] = $shopCategory->image;
            }
        }

        return $data;
    }

    protected function deleteCategoryMedia(ShopCategory $shopCategory): void
    {
        if ($shopCategory->image) {
            Storage::disk('public')->delete($shopCategory->image);
        }
        foreach ($shopCategory->images ?? [] as $path) {
            if ($path && $path !== $shopCategory->image) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    protected function storeUploadedImages(Request $request, string $key, array $existing): array
    {
        $paths = $existing;
        if ($request->hasFile($key)) {
            $files = $request->file($key);
            foreach (is_array($files) ? $files : [$files] as $file) {
                if ($file && $file->isValid()) {
                    $paths[] = $file->store('shop/gallery', 'public');
                }
            }
        }
        return array_values(array_filter($paths));
    }
}
