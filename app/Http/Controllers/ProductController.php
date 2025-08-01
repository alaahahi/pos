<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class ProductController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:read product', ['only' => ['index']]);
        $this->middleware('permission:create product', ['only' => ['create']]);
        $this->middleware('permission:update product', ['only' => ['update','edit']]);
        $this->middleware('permission:delete product', ['only' => ['destroy']]);
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        

        // Define the filters
        $filters = [
            'name' => $request->name,
            'model' => $request->model,
            'is_active' => $request->is_active,
        ];
        // Start the Product query
        $ProductQuery = Product::with('roles')->latest();

        // Apply the filters if they exist
        $ProductQuery->when($filters['name'], function ($query, $name) {
            return $query->where('name', 'LIKE', "%{$name}%");
        });

        $ProductQuery->when($filters['model'], function ($query, $model) {
            return $query->where('model', 'LIKE', "%{$model}%");
        });


        if (isset($filters['is_active'])) {
            $ProductQuery->where('is_active', $filters['is_active']);
        }
        // Paginate the filtered product
        $product = $ProductQuery->paginate(10);

        return Inertia('Products/index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'products' => $product,
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::pluck('name', 'name')->all();
        return Inertia('Products/Create', [ 'translations' => __('messages'),'roles' => $roles]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
         // التحقق من البيانات
         $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:100',
            'note' => 'nullable|string',
            'oe_number' => 'nullable|string|max:100',
            'price_cost' => 'nullable|numeric|min:0',
            'transport' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'situation' => 'nullable|in:available,unavailable,damaged',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'barcode' => 'nullable|integer|unique:products,barcode',
        ]);
        
        // توليد barcode تلقائي إذا لم يتم إرساله
        if (empty($validated['barcode'])) {
            do {
                $generatedBarcode = random_int(10000000, 99999999); // رقم عشوائي من 8 أرقام
            } while (Product::where('barcode', $generatedBarcode)->exists());
        
            $validated['barcode'] = $generatedBarcode;
        }
        
        // تعيين التاريخ
        $validated['created'] = Carbon::now()->format('Y-m-d');
        
        // رفع الصورة إذا تم إرسال ملف
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = $path;
        } else {
            $validated['image'] = 'products/default_product.png';
        }
        
        // إنشاء المنتج
        $product = Product::create($validated);
        
        // مزامنة الصلاحيات إذا وُجدت
        if ($request->has('selectedRoles')) {
            $product->syncRoles($request->selectedRoles);
        }
        
        // إعادة التوجيه مع رسالة نجاح
        return redirect()->route('products.index')->with('success', __('messages.data_saved_successfully'));
        
    }
    

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $roles = Role::pluck('name', 'name')->all();
        $productRoles = $product->roles->pluck('name')->all();
        return Inertia('Products/Edit', [
            'translations' => __('messages'),
            'product' => $product,
            'roles' => $roles,
            'productRoles' => $productRoles
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:100',
            'note' => 'nullable|string',
            'oe_number' => 'nullable|string|max:100',
            'price_cost' => 'required|numeric|min:0',
            'transport' => 'nullable|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'price' => 'required|numeric|min:0',
            'situation' => 'nullable',//|in:available,unavailable,damaged
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'barcode' => 'nullable|integer|unique:products,barcode,' . $product->id,
        ]);
    
        // توليد barcode إذا لم يكن موجودًا مسبقًا
        if (empty($validated['barcode']) && !$product->barcode) {
            do {
                $generatedBarcode = random_int(10000000, 99999999);
            } while (Product::where('barcode', $generatedBarcode)->where('id', '!=', $product->id)->exists());
    
            $validated['barcode'] = $generatedBarcode;
        }
    
        // تحديث التاريخ
        $validated['created'] = Carbon::now()->format('Y-m-d');
    
        // التعامل مع رفع الصورة الجديدة
        if ($request->hasFile('image')) {
            // حذف الصورة القديمة إذا لم تكن الصورة الافتراضية
            if ($product->image && $product->image !== 'products/default_product.png') {
                Storage::disk('public')->delete($product->image);
            }
    
            // رفع الصورة الجديدة
            $path = $request->file('image')->store('products', 'public');
            $validated['image'] = $path;
        }
    
        // تحديث المنتج
        $product->update($validated);
    
        // مزامنة الأدوار إذا تم تمريرها
        if ($request->has('selectedRoles')) {
            $product->syncRoles($request->selectedRoles);
        }
    
        return redirect()->route('products.index')->with('success', __('messages.data_updated_successfully'));
    }
    

    public function activate(Product $product)
    {
        $product->update(
            [
                'is_active' => ($product->is_active) ? 0 : 1
            ]
        );
        return redirect()->route('products.index')
            ->with('success', 'product Status Updated successfully!');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // تحقق ما إذا كان المنتج محذوفًا بالفعل
        if ($product->trashed()) {
            return redirect()->route('products.index')
                ->with('error', __('messages.product_already_deleted'));
        }
    
        // حذف المنتج حذفًا ناعمًا
        $product->delete();
    
        return redirect()->route('products.index')
            ->with('success', __('messages.data_deleted_successfully'));
    }
    public function trashed()
    {
        $trashedProducts = Product::onlyTrashed()->get();
        return view('products.trashed', compact('trashedProducts'));
    }
    public function restore($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return redirect()->route('products.index')
            ->with('success', __('messages.product_restored_successfully'));
    }

    public function findByBarcode($barcode)
    {
            // جلب المنتجات من الكاش أو إعادة تعبئتها إذا كانت غير موجودة
        $products = Cache::get('all_products');
        
        // إذا كانت المنتجات غير موجودة في الكاش
        if (!$products) {
            // جلب المنتجات من قاعدة البيانات مع إضافة شرط is_active = 1
            $products = Cache::remember('all_products', 600, function () {
                return DB::table('products')
                    ->select('id', 'name', 'price', 'barcode')
                    ->where('is_active', 1)
                    ->get();
            });
        }
        
        // البحث عن المنتج باستخدام الباركود من الكاش
        $product = $products->firstWhere('barcode', $barcode);
        
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 200);
        }
        
        return response()->json($product);
    }
}
