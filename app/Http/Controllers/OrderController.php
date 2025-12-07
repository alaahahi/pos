<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use App\Models\UserType;
use App\Models\SystemConfig;
use App\Models\User;
// use App\Http\Requests\Order\StoreOrderRequest;
// use App\Http\Requests\Order\UpdateOrderRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AccountingController;
use App\Services\LogService;


class OrderController extends Controller
{
    public function __construct(AccountingController $accountingController)
    {
        $this->accountingController = $accountingController;
        // Middleware for permission handling
        $this->middleware('permission:read order', ['only' => ['index']]);
        $this->middleware('permission:create order', ['only' => ['create', 'store']]);
        $this->middleware('permission:update order', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete order', ['only' => ['destroy']]);
        $this->userAccount = UserType::where('name', 'account')->first()?->id;
        $this->mainBox= User::with('wallet')->where('type_id', $this->userAccount)->where('email','mainBox@account.com')->first();
        $this->defaultCurrency = env('DEFAULT_CURRENCY', 'IQD'); // مثلاً 'KWD' كخيار افتراضي

    }

    /**
     * Display a listing of the orders.
     */
    public function index(Request $request)
    {
        $filters = [
            'customer' => $request->customer,
            'status' => $request->status,
            'order_date' => $request->order_date,
        ];

        // Start the Order query
        $orderQuery = Order::with('customer', 'products')->latest();

        // Apply filters
        $orderQuery->when($filters['customer'], function ($query, $customer) {
            return $query->where('customer_id', $customer);
        });

        $orderQuery->when($filters['status'], function ($query, $status) {
            return $query->where('status', $status);
        });

        $orderQuery->when($filters['order_date'], function ($query, $order_date) {
            return $query->whereDate('order_date', $order_date);
        });

        $orders = $orderQuery->paginate(10);
        
        // Get statistics
        $statistics = $this->getOrderStatistics();

        return Inertia::render('Orders/index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'orders' => $orders,
            'statistics' => $statistics,
        ]);
    }

    /**
     * Show the form for creating a new order.
     */
    public function create()
    {
        // Get customers and products to populate dropdowns
        $customers = Customer::select('id', 'name','phone')->get()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ];
        });
        $defaultCustomer = Customer::where('name', 'زبون افتراضي')->select('id', 'name', 'phone')->first();
        // Get all active products with their categories
        $products = Product::where('is_active', true)
            ->with('category:id,name,color,icon')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'model' => $product->model,
                    'price' => $product->price,
                    'quantity' => $product->quantity,
                    'max_quantity' => $product->quantity,
                    'image_url' => $product->image_url, // استخدام accessor من Model
                    'barcode' => $product->barcode,
                    'is_featured' => $product->is_featured,
                    'is_best_selling' => $product->is_best_selling,
                    'sales_count' => $product->sales_count,
                    'category_id' => $product->category_id,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'color' => $product->category->color,
                        'icon' => $product->category->icon,
                    ] : null,
                ];
            });

        // Get all active categories
        $categories = \App\Models\Category::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'color' => $category->color,
                    'icon' => $category->icon,
                    'slug' => $category->slug,
                ];
            });

        // Get today's sales statistics
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();
        
        $todaySales = Order::whereBetween('created_at', [$todayStart, $todayEnd])
            ->selectRaw('
                COUNT(*) as orders_count,
                SUM(total_amount) as total_sales,
                SUM(total_paid) as total_paid,
                SUM(total_amount - total_paid) as total_due
            ')
            ->first();

        return Inertia::render('Orders/Create', [
            'translations' => __('messages'),
            'customers' => $customers,
            'products' => $products,
            'categories' => $categories,
            'defaultCustomer' => $defaultCustomer,
            'defaultCurrency' => $this->defaultCurrency,
            'companyLogo' => env('COMPANY_LOGO', 'dashboard-assets/img/logo.png'),
            'todaySales' => [
                'orders_count' => $todaySales->orders_count ?? 0,
                'total_sales' => $todaySales->total_sales ?? 0,
                'total_paid' => $todaySales->total_paid ?? 0,
                'total_due' => $todaySales->total_due ?? 0,
            ]
        ]);
    }

    /**
     * Store a newly created order in storage.
     */

    
     public function createOrder(Request $request)
     {
         // التحقق من صحة البيانات
         $validated = $request->validate([
             'customer_id' => 'required|exists:customers,id',
             'total_amount' => 'required|numeric|min:0',
             'total_paid' => 'numeric|min:0',
             'notes' => 'nullable|string',
             'items' => 'required|array|min:1',
             'items.*.product_id' => 'required|exists:products,id',
             'items.*.quantity' => 'required|integer|min:1',
             'items.*.price' => 'required|numeric|min:0',
             'discount_amount' => 'nullable|numeric|min:0',
             'discount_rate' => 'nullable|numeric|min:0|max:100',
         ]);
     
         DB::beginTransaction();
         try {
             // حساب الخصم والمبلغ النهائي
             $discountAmount = $validated['discount_amount'] ?? 0;
             $discountRate = $validated['discount_rate'] ?? 0;
             
             // إذا كان الخصم كنسبة مئوية
             if ($discountRate > 0) {
                 $discountAmount = ($validated['total_amount'] * $discountRate) / 100;
             }
             
             $finalAmount = $validated['total_amount'] - $discountAmount;
             
            // إنشاء الطلب
             $order = Order::create([
                 'customer_id' => $validated['customer_id'],
                 'payment_method' => 'cash',
                 'status' =>  $finalAmount == $validated['total_paid'] ?  'paid' : 'due',
                 'total_amount' => $validated['total_amount'],
                 'total_paid' => $validated['total_paid'] ?? 0,
                 'date' => $request->date  ?? Carbon::now()->format('Y-m-d'),
                 'notes' => $validated['notes'] ?? '',
                 'discount_amount' => $discountAmount,
                 'discount_rate' => $discountRate,
                 'final_amount' => $finalAmount,
             ]);
     
             // إرفاق المنتجات مع الكميات
             $products = [];
             foreach ($validated['items'] as $productData) {
                 $products[$productData['product_id']] = [
                     'quantity' => $productData['quantity'],
                     'price' => $productData['price'],
                 ];
             }
             $order->products()->sync($products);
     
             // تنزيل الكمية من المخزن
             foreach ($validated['items'] as $productData) {
                 $product = Product::find($productData['product_id']);

                 if ($product) {
                     if ($product->quantity < $productData['quantity']) {
                         throw new \Exception("الكمية غير متوفرة للمادة: {$product->name}");
                     }
                     $product->quantity -= $productData['quantity'];
                     $product->save();
                 }
             }
     
            // إنشاء المعاملات المالية إذا كان هناك مبلغ مدفوع
            $transaction = null;
            if ($validated['total_paid'] > 0) {
                if (!$this->mainBox) {
                    throw new \Exception("الصندوق الرئيسي غير موجود، يرجى إضافة المستخدم الأساسي");
                }
                $transaction = $this->accountingController->increaseWallet(
                    $validated['total_paid'], // المبلغ المدفوع
                    'دفع نقدي فاتورة رقم ' . $order->id,
                    $this->mainBox->id, // الصندوق الرئيسي
                    $order->id, // ربط المعاملة بالطلب
                    Order::class, // نوع النموذج المرتبط
                    0,
                    0,
                    $this->defaultCurrency,
                    $order->date
                );
            }

            // تسجيل العملية في logs table
            LogService::createLog(
                'Order',
                'Created',
                $order->id,
                [],
                $order->toArray(),
                'success'
            );
    
            DB::commit();
     
             // إذا كان الطلب من API، أرجع JSON
             if ($request->expectsJson()) {
                 return response()->json([
                     'message' => __('messages.data_saved_successfully'),
                     'order_id' => $order->id,
                     'id' => isset($transaction) ? $transaction->id : null
                 ], 201);
             }
     
             return redirect()->route('orders.index')->with('success', __('messages.data_saved_successfully'));
         } catch (\Exception $e) {
             DB::rollBack();
            Log::error('Order creation failed', ['error' => $e->getMessage()]);
     
             if ($request->expectsJson()) {
                 return response()->json(['error' => $e->getMessage()], 500);
             }
     
             return redirect()->back()->with('error',$e->getMessage());
         }
     }
    

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order)
    {
        $order->load('products'); // Load the products relationship
        //dd($order->products->toArray());
        $orderItems = $order->products->map(function ($product) {
            return [
                'id' => $product->pivot->id ?? null, // Optional: ID of the pivot table row if needed
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $product->pivot->quantity,
                'price' => $product->pivot->price,
            ];
        });
    
        $customers = Customer::select('id', 'name', 'phone')->get()->map(function ($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ];
        });
    
        $orderedProductIds = $order->products->pluck('id')->toArray();

        // Fetch products that are not in the order
        $products = Product::whereNotIn('id', $orderedProductIds)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'model' => $product->model,
                    'price' => $product->price,
                    'quantity' => $product->quantity,
                    'max_quantity' => $product->quantity,
                    'image_url' => $product->image_url, // استخدام accessor من Model
                    'barcode' => $product->barcode,
                ];
            });
            $all_products = Product::get()->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'model' => $product->model,
                    'price' => $product->price,
                    'quantity' => $product->quantity,
                    'max_quantity' => $product->quantity,
                    'image_url' => $product->image_url, // استخدام accessor من Model
                    'barcode' => $product->barcode,
                ];
            });
        return Inertia::render('Orders/Edit', [
            'translations' => __('messages'),
            'order' => [
                'id' => $order->id,
                'customer_id' => $order->customer_id,
                'total_amount' => $order->total_amount,
                'items' => $orderItems,
            ],
            'customers' => $customers,
            'products' => $products,
            'all_products' => $all_products,
        ]);
    }
    

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'total_amount' => 'required|numeric|min:0',
            'total_paid' => 'numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_rate' => 'nullable|numeric|min:0|max:100',
        ]);
    
        DB::beginTransaction();
    
        try {
            // حساب الخصم والمبلغ النهائي
            $discountAmount = $validated['discount_amount'] ?? 0;
            $discountRate = $validated['discount_rate'] ?? 0;
            
            // إذا كان الخصم كنسبة مئوية
            if ($discountRate > 0) {
                $discountAmount = ($validated['total_amount'] * $discountRate) / 100;
            }
            
            $finalAmount = $validated['total_amount'] - $discountAmount;
            
            // تحديث تفاصيل الفاتورة
            $order->update([
                'status' => $finalAmount == $validated['total_paid'] ? 'paid' : 'due',
                'total_paid' => $validated['total_paid'] ?? 0,
                'customer_id' => $validated['customer_id'],
                'total_amount' => $validated['total_amount'],
                'date' => $request->date ?? Carbon::now()->format('Y-m-d'),
                'notes' => $validated['notes'] ?? '',
                'discount_amount' => $discountAmount,
                'discount_rate' => $discountRate,
                'final_amount' => $finalAmount,
            ]);
    
            // العناصر القديمة (قبل التعديل)
            $oldItems = $order->products->keyBy('id');
    
            // العناصر الجديدة (من الفورم)
            $newItems = collect($validated['items'])->keyBy('product_id');
    
            // تجهيز بيانات المزامنة
            $syncData = [];
    
            // مقارنة القديم مع الجديد
            foreach ($newItems as $productId => $item) {
                $product = Product::find($productId);
                if (!$product) continue;
    
                $oldQty = $oldItems[$productId]->pivot->quantity ?? 0;
                $newQty = $item['quantity'];
    
                // إذا زادت الكمية → ننقص الفرق من المخزن
                if ($newQty > $oldQty) {
                    $diff = $newQty - $oldQty;
                    if ($product->quantity < $diff) {
                        throw new \Exception("الكمية غير متوفرة للمادة: {$product->name}");
                    }
                    $product->quantity -= $diff;
                }
                // إذا نقصت الكمية → نرجع الفرق للمخزن
                elseif ($newQty < $oldQty) {
                    $diff = $oldQty - $newQty;
                    $product->quantity += $diff;
                }
    
                $product->save();
    
                // تجهيز بيانات الـ pivot
                $syncData[$productId] = [
                    'quantity' => $newQty,
                    'price'    => $item['price'],
                ];
            }

              if($validated['total_paid']>0){
                 if (!$this->mainBox) {
                     throw new \Exception("الصندوق الرئيسي غير موجود، يرجى إضافة المستخدم الأساسي");
                 }
                 $transaction = $this->accountingController->increaseWallet(
                     $validated['total_paid'], // المبلغ المدفوع
                     'دفع نقدي فاتورة رقم ' . $order->id, // الوصف
                     $this->mainBox->id, // الصندوق الرئيسي للعميل
                     $order->id, // ربط المعاملة بالطلب
                     Order::class, // نوع النموذج المرتبط
                     0,
                     0,
                     $this->defaultCurrency,
                     $order->date
                 );
             }
    
            // المنتجات اللي كانت موجودة بالقديم وانحذفت في الجديد → نرجع مخزونها
            $removedProducts = $oldItems->keys()->diff($newItems->keys());
    
            foreach ($removedProducts as $removedId) {
                $removedProduct = Product::find($removedId);
                if ($removedProduct) {
                    $removedProduct->quantity += $oldItems[$removedId]->pivot->quantity;
                    $removedProduct->save();
                }
            }
    
            // تحديث العلاقة في Pivot Table
            $order->products()->sync($syncData);
    
            // حفظ التغييرات
            DB::commit();
    
            // تسجيل عملية التحديث
            LogService::createLog(
                'Order',
                'Updated',
                $order->id,
                $oldItems->toArray(),
                $order->fresh()->toArray(),
                'warning'
            );
    
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.data_saved_successfully'),
                    'order_id' => $order->id,
                ], 201);
            }
    
            return redirect()->route('orders.index')
                ->with('success', __('messages.data_updated_successfully'));
    
        } catch (\Exception $e) {
            DB::rollBack();
    
            Log::error('Error updating order', ['error' => $e->getMessage()]);
    
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
    
            return back()->withErrors(['error' => __('messages.error_occurred')]);
        }
    }
    
    
    

    /**
     * Remove the specified order from storage.
     */
  public function destroy(Order $order)
    {
        DB::beginTransaction();

        try {
            // استرجاع جميع المواد المرتبطة بالطلب (إرجاع الكمية للمخزون)
            foreach ($order->products as $product) {
                $qty = $product->pivot->quantity;

                $product->quantity += $qty;
                $product->save();
            }

            // إذا كان هناك مبلغ مدفوع → نرجعه من الصندوق
            if ($order->total_paid > 0) {
                if (!$this->mainBox) {
                    throw new \Exception("الصندوق الرئيسي غير موجود، لا يمكن استرجاع المبلغ");
                }
                $this->accountingController->decreaseWallet(
                    $order->total_paid, // كل المبلغ المدفوع
                    'استرجاع دفعة بعد حذف فاتورة رقم ' . $order->id,
                    $this->mainBox->id, // الصندوق الرئيسي
                    $this->mainBox->id, // صندوق النظام
                    'App\Models\User',
                    0,
                    0,
                    $this->defaultCurrency,
                    $order->date
                );
            }

            // حذف الفاتورة (soft delete)
            $order->delete();

            DB::commit();

            // Log deletion
            LogService::createLog(
                'Order',
                'Deleted',
                $order->id,
                $order->toArray(),
                [],
                'danger'
            );

            return redirect()->route('orders.index')
                ->with('success', __('messages.data_deleted_successfully'));

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deleting order', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
            ]);

            return redirect()->route('orders.index')
                ->with('error', __('messages.error_occurred'));
        }
    }

    
    
    /**
     * Change the status of an order.
     */
    public function changeStatus(Order $order)
    {
        $order->update([
            'status' => ($order->status === 'pending') ? 'completed' : 'pending'
        ]);

        return redirect()->route('orders.index')
            ->with('success', __('messages.status_updated_successfully'));
    }
    public function activate(Order $order)
{
    $order->update([
        'status' => ($order->status === 'pending') ? 'completed' : 'pending'
    ]);

    return redirect()->route('orders.index')
        ->with('success', __('messages.status_updated_successfully'));
}

public function trashed()
{
    // Retrieve trashed orders
    $trashedOrders = Order::onlyTrashed()->get();
    
    // Return the trashed orders to the view
    return Inertia::render('Orders/Trashed', [
        'trashedOrders' => $trashedOrders,
        'translations' => __('messages'),
    ]);
}

public function restore($id)
{
    // Find and restore the soft-deleted order
    $order = Order::onlyTrashed()->findOrFail($id);
    $order->restore();

    return redirect()->route('orders.index')
        ->with('success', __('messages.order_restored_successfully'));
}

/**
 * Get today's sales statistics
 */
public function getTodaySales()
{
    $todayStart = Carbon::today();
    $todayEnd = Carbon::today()->endOfDay();
    
    $todaySales = Order::whereBetween('created_at', [$todayStart, $todayEnd])
        ->selectRaw('
            COUNT(*) as orders_count,
            SUM(total_amount) as total_sales,
            SUM(total_paid) as total_paid,
            SUM(total_amount - total_paid) as total_due
        ')
        ->first();

    return response()->json([
        'orders_count' => $todaySales->orders_count ?? 0,
        'total_sales' => $todaySales->total_sales ?? 0,
        'total_paid' => $todaySales->total_paid ?? 0,
        'total_due' => $todaySales->total_due ?? 0,
    ]);
}

public function print($id)
{
    // Retrieve the order with its related customer and products
    $order = Order::with(['customer', 'products'])->findOrFail($id);

    // Return the view with the order data
    return view('orders.print-invoice', compact('order'));
}

/**
 * Get order statistics
 */
private function getOrderStatistics()
{
    $today = now()->startOfDay();
    $yesterday = now()->subDay()->startOfDay();
    $weekStart = now()->startOfWeek();
    $monthStart = now()->startOfMonth();
    
    // Today's orders
    $todayOrders = Order::whereDate('created_at', $today)->get();
    $todayCount = $todayOrders->count();
    $todayTotal = $todayOrders->sum('final_amount') ?: $todayOrders->sum('total_amount');
    $todayPaid = $todayOrders->sum('total_paid');
    
    // Yesterday's orders
    $yesterdayOrders = Order::whereDate('created_at', $yesterday)->get();
    $yesterdayCount = $yesterdayOrders->count();
    $yesterdayTotal = $yesterdayOrders->sum('final_amount') ?: $yesterdayOrders->sum('total_amount');
    
    // Week's orders
    $weekOrders = Order::where('created_at', '>=', $weekStart)->get();
    $weekCount = $weekOrders->count();
    $weekTotal = $weekOrders->sum('final_amount') ?: $weekOrders->sum('total_amount');
    $weekPaid = $weekOrders->sum('total_paid');
    
    // Month's orders
    $monthOrders = Order::where('created_at', '>=', $monthStart)->get();
    $monthCount = $monthOrders->count();
    $monthTotal = $monthOrders->sum('final_amount') ?: $monthOrders->sum('total_amount');
    $monthPaid = $monthOrders->sum('total_paid');
    
    // Calculate percentage change from yesterday
    $countChange = 0;
    $totalChange = 0;
    
    if ($yesterdayCount > 0) {
        $countChange = round((($todayCount - $yesterdayCount) / $yesterdayCount) * 100, 1);
    } else if ($todayCount > 0) {
        $countChange = 100;
    }
    
    if ($yesterdayTotal > 0) {
        $totalChange = round((($todayTotal - $yesterdayTotal) / $yesterdayTotal) * 100, 1);
    } else if ($todayTotal > 0) {
        $totalChange = 100;
    }
    
    return [
        'today' => [
            'count' => $todayCount,
            'total' => round($todayTotal, 2),
            'paid' => round($todayPaid, 2),
            'due' => round($todayTotal - $todayPaid, 2),
        ],
        'yesterday' => [
            'count' => $yesterdayCount,
            'total' => round($yesterdayTotal, 2),
        ],
        'week' => [
            'count' => $weekCount,
            'total' => round($weekTotal, 2),
            'paid' => round($weekPaid, 2),
            'due' => round($weekTotal - $weekPaid, 2),
        ],
        'month' => [
            'count' => $monthCount,
            'total' => round($monthTotal, 2),
            'paid' => round($monthPaid, 2),
            'due' => round($monthTotal - $monthPaid, 2),
        ],
        'changes' => [
            'count' => $countChange,
            'total' => $totalChange,
        ],
    ];
}

}
