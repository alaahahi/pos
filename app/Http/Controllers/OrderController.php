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
        $this->userAccount =  UserType::where('name', 'account')->first()?->id;
        $this->mainBox= User::with('wallet')->where('type_id', $this->userAccount)->where('email','mainBox@account.com');
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

        return Inertia::render('Orders/index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'orders' => $orders,
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
        $products = Product::select('id', 'name', 'model', 'price', 'quantity', 'image', 'barcode')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'model' => $product->model,
                'price' => $product->price,
                'quantity' => $product->quantity,
                'max_quantity' => $product->quantity,
                'image_url' => $product->image ? asset("storage/{$product->image}") : null,
                'barcode' => $product->barcode,
            ];
        });

        return Inertia::render('Orders/Create', [
            'translations' => __('messages'),
            'customers' => $customers,
            'products' => $products,
            'defaultCustomer' => $defaultCustomer,
            'defaultCurrency' => $this->defaultCurrency
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
                $transaction = $this->accountingController->increaseWallet(
                    $validated['total_paid'], // المبلغ المدفوع
                    'دفع نقدي فاتورة رقم ' . $order->id,
                    $this->mainBox->first()->id, // الصندوق الرئيسي
                    $this->mainBox->first()->id, // صندوق النظام
                    'App\Models\User',
                    0,
                    0,
                    $this->defaultCurrency,
                    $order->date
                );
            }

            // تسجيل العملية في السجلات
            Log::info('Order created', ['order_id' => $order->id, 'customer_id' => $order->customer_id]);
    
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
        $products = Product::select('id', 'name', 'model', 'price', 'quantity', 'image', 'barcode')
            ->whereNotIn('id', $orderedProductIds)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'model' => $product->model,
                    'price' => $product->price,
                    'quantity' => $product->quantity,
                    'max_quantity' => $product->quantity,
                    'image_url' => $product->image ? asset("storage/{$product->image}") : null,
                    'barcode' => $product->barcode,
                ];
            });
            $all_products = Product::select('id', 'name', 'model', 'price', 'quantity', 'image', 'barcode')->get()->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'model' => $product->model,
                    'price' => $product->price,
                    'quantity' => $product->quantity,
                    'max_quantity' => $product->quantity,
                    'image_url' => $product->image ? asset("storage/{$product->image}") : null,
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
                 $transaction = $this->accountingController->increaseWallet(
                     $validated['total_paid'], // المبلغ المدفوع
                     'دفع نقدي فاتورة رقم ' . $order->id, // الوصف
                     $this->mainBox->first()->id, // الصندوق الرئيسي للعميل
                     $this->mainBox->first()->id, // صندوق النظام أو المستخدم الأساسي
                     'App\Models\User',
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
    
            Log::info('Order updated successfully', ['order_id' => $order->id]);
    
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
                $this->accountingController->decreaseWallet(
                    $order->total_paid, // كل المبلغ المدفوع
                    'استرجاع دفعة بعد حذف فاتورة رقم ' . $order->id,
                    $this->mainBox->first()->id, // الصندوق الرئيسي
                    $this->mainBox->first()->id, // صندوق النظام
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

            // Log
            Log::info('Order deleted, stock restored, and payments refunded', [
                'order_id' => $order->id,
                'refunded_amount' => $order->total_paid,
            ]);

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

public function print($id)
{
    // Retrieve the order with its related customer and products
    $order = Order::with(['customer', 'products'])->findOrFail($id);

    // Return the view with the order data
    return view('orders.print-invoice', compact('order'));
}

}
