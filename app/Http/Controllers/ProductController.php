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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\LogService;
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
            'search' => $request->search,
            'status' => $request->status,
            'stock' => $request->stock,
            'sort' => $request->sort ?? 'created',
        ];
        
        // Start the Product query with sales count using subquery
        $ProductQuery = Product::with('roles')
            ->leftJoin(\DB::raw('(SELECT product_id, SUM(quantity) as total_quantity FROM order_items GROUP BY product_id) as order_items'), 
                'products.id', '=', 'order_items.product_id')
            ->select('products.*', \DB::raw('COALESCE(order_items.total_quantity, 0) as total_sales'));

        // Apply search filter
        $ProductQuery->when($filters['search'], function ($query, $search) {
            return $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('barcode', 'LIKE', "%{$search}%")
                  ->orWhere('model', 'LIKE', "%{$search}%");
            });
        });

        // Apply status filter
        $ProductQuery->when($filters['status'], function ($query, $status) {
            if ($status === 'active') {
                return $query->where('is_active', 1);
            } elseif ($status === 'inactive') {
                return $query->where('is_active', 0);
            }
            return $query;
        });

        // Apply stock filter
        $ProductQuery->when($filters['stock'], function ($query, $stock) {
            if ($stock === 'low') {
                return $query->where('products.quantity', '<=', 5)->where('products.quantity', '>', 0);
            } elseif ($stock === 'out') {
                return $query->where('products.quantity', 0);
            } elseif ($stock === 'available') {
                return $query->where('products.quantity', '>', 5);
            }
            return $query;
        });

        // Apply sorting
        switch ($filters['sort']) {
            case 'name':
                $ProductQuery->orderBy('products.name', 'asc');
                break;
            case 'price':
                $ProductQuery->orderBy('products.price', 'desc');
                break;
            case 'quantity':
                $ProductQuery->orderBy('products.quantity', 'desc');
                break;
            case 'best_selling':
                $ProductQuery->orderBy('total_sales', 'desc');
                break;
            case 'created':
            default:
                $ProductQuery->orderBy('products.created_at', 'desc');
                break;
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
            'notes' => 'nullable|string',
            'oe_number' => 'nullable|string|max:100',
            'price_cost' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'barcode' => 'nullable|integer|unique:products,barcode',
        ]);
        
        // توليد barcode تلقائي إذا لم يتم إرساله
        if (empty($validated['barcode'])) {
            do {
                $generatedBarcode = random_int(1000000000, 9999999999); // رقم عشوائي من 8 أرقام
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

        // Log product creation (redundant with model event but ensures coverage when events disabled)
        LogService::createLog(
            'Product',
            'Created',
            $product->id,
            [],
            $product->toArray(),
            'success'
        );
        
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
        // التحقق من البيانات
        $originalData = $product->getOriginal();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'model' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'oe_number' => 'nullable|string|max:100',
            'price_cost' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'price' => 'nullable|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'barcode' => 'nullable|integer|unique:products,barcode,' . $product->id,
        ]);
        
        // توليد barcode تلقائي إذا لم يتم إرساله ولم يكن موجودًا
        if (empty($validated['barcode']) && !$product->barcode) {
            do {
                $generatedBarcode = random_int(1000000000, 9999999999); // رقم عشوائي من 8 أرقام
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

        // Log product update (redundant with model event but ensures coverage when events disabled)
        LogService::createLog(
            'Product',
            'Updated',
            $product->id,
            $originalData,
            $product->toArray(),
            'warning'
        );
        
        // مزامنة الصلاحيات إذا وُجدت
        if ($request->has('selectedRoles')) {
            $product->syncRoles($request->selectedRoles);
        }
        
        // إعادة التوجيه مع رسالة نجاح
        return redirect()->route('products.index')->with('success', __('messages.data_updated_successfully'));
    }
    

    public function activate(Product $product)
    {
        $originalData = $product->getOriginal();
        $product->update(
            [
                'is_active' => ($product->is_active) ? 0 : 1
            ]
        );
        LogService::createLog(
            'Product',
            'Status Toggled',
            $product->id,
            $originalData,
            $product->toArray(),
            'info'
        );
        return redirect()->route('products.index')
            ->with('success', 'product Status Updated successfully!');
    }

    public function toggleFeatured(Product $product)
    {
        $originalData = $product->getOriginal();
        $product->update([
            'is_featured' => !$product->is_featured
        ]);
        LogService::createLog(
            'Product',
            'Featured Toggled',
            $product->id,
            $originalData,
            $product->toArray(),
            'info'
        );
        
        return redirect()->back()->with('success', 'تم تحديث حالة المنتج المميز بنجاح');
    }

    public function toggleBestSelling(Product $product)
    {
        $originalData = $product->getOriginal();
        $product->update([
            'is_best_selling' => !$product->is_best_selling
        ]);
        LogService::createLog(
            'Product',
            'BestSelling Toggled',
            $product->id,
            $originalData,
            $product->toArray(),
            'info'
        );
        
        return redirect()->back()->with('success', 'تم تحديث حالة المنتج الأكثر مبيعاً بنجاح');
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
        $originalData = $product->toArray();
        $id = $product->id;
        $product->delete();
        LogService::createLog(
            'Product',
            'Deleted',
            $id,
            $originalData,
            [],
            'danger'
        );
    
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

    /**
     * البحث في المنتجات بالاسم أو الباركود أو الموديل
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProducts(Request $request)
    {
        $query = $request->input('query');
        
        if (empty($query)) {
            return response()->json([]);
        }

        // البحث في المنتجات النشطة فقط
        $products = Product::where('is_active', 1)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('barcode', 'LIKE', "%{$query}%")
                  ->orWhere('model', 'LIKE', "%{$query}%");
            })
            ->select('id', 'name', 'model', 'price', 'barcode', 'quantity', 'image', 'is_featured', 'is_best_selling')
            ->limit(50) // تحديد النتائج بـ 50 منتج
            ->get();

        // استخدام image_url accessor من Model (سيتم إضافته تلقائياً)
        return response()->json($products);
    }

    /**
     * Check if the product is available in the stock
     * 
     * @param int $product_id
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Process product purchase with optional box withdrawal
     * 
     * @param Request $request
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function processPurchase(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price_cost' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'date' => 'required|date',
            'withdraw_from_box' => 'boolean',
            'currency' => 'required|in:IQD,$'
        ]);

        $product = Product::find($request->product_id);
         if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'المنتج غير موجود'
            ], 404);
        }
        
        // Calculate total cost
        $totalCost = $request->quantity * $request->price_cost;
        
        // Update product quantity and prices
        $product->update([
            'quantity' => $product->quantity + $request->quantity,
            'price_cost' => $request->price_cost,
            'price' => $request->price,
            'created' => $request->date
        ]);

        $response = [
            'success' => true,
            'message' => 'تم تحديث المنتج بنجاح',
            'product' => $product,
            'total_cost' => $totalCost,
            'currency' => $request->currency
        ];

        // If withdrawal from box is requested
        if ($request->withdraw_from_box) {
            try {
                // Get main box
                $userAccount = \App\Models\UserType::where('name', 'account')->first();
                $mainBox = \App\Models\User::with('wallet')
                    ->where('type_id', $userAccount->id)
                    ->where('email', 'mainBox@account.com')
                    ->first();

                if (!$mainBox || !$mainBox->wallet) {
                    return response()->json([
                        'success' => false,
                        'message' => 'الصندوق الرئيسي غير موجود'
                    ], 400);
                }

                // Create withdrawal transaction
                $accountingController = new \App\Http\Controllers\AccountingController();
                $transaction = $accountingController->decreaseWallet(
                    $totalCost, // المبلغ المدفوع
                    "شراء منتج: {$product->name} - الكمية: {$request->quantity}", // الوصف
                    $mainBox->id, // الصندوق الرئيسي
                    $mainBox->id, // صندوق النظام
                    'App\Models\User',
                    0,
                    0,
                    $request->currency,
                    $request->date
                );

                $response['box_transaction'] = $transaction;
                $response['message'] = 'تم تحديث المنتج وسحب المبلغ من الصندوق بنجاح';

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء السحب من الصندوق: ' . $e->getMessage()
                ], 500);
            }
        }

        return response()->json($response);
    }

    /**
     * Check if barcode is unique
     * 
     * @param string $barcode
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkBarcodeUnique($barcode)
    {
        $exists = Product::where('barcode', $barcode)->exists();
        
        return response()->json([
            'unique' => !$exists,
            'message' => $exists ? 'الباركود مستخدم بالفعل' : 'الباركود متاح'
        ]);
    }

    /**
     * Check if the product is available in the stock
     * 
     * @param int $product_id
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStock($product_id)
    {
        $product = Product::find($product_id);
        if ($product->quantity <= 0) {
            return response()->json(['message' => 'Product not available'], 409);
        }else{
            return response()->json(['available_quantity' => $product->quantity], 200);
        }
     }       
}
