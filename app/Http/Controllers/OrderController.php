<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
// use App\Http\Requests\Order\StoreOrderRequest;
// use App\Http\Requests\Order\UpdateOrderRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;



class OrderController extends Controller
{
    public function __construct()
    {
        // Middleware for permission handling
        $this->middleware('permission:read order', ['only' => ['index']]);
        $this->middleware('permission:create order', ['only' => ['create', 'store']]);
        $this->middleware('permission:update order', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete order', ['only' => ['destroy']]);
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
        $products = Product::select('id', 'name','price','quantity')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'max_quantity'=> $product->quantity,
            ];
        });

        return Inertia::render('Orders/Create', [
            'translations' => __('messages'),
            'customers' => $customers,
            'products' => $products,
            'defaultCustomer' => $defaultCustomer
        ]);
    }

    /**
     * Store a newly created order in storage.
     */

    
    public function store(Request $request)
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
        ]);
    
        DB::beginTransaction();
        try {
            // إنشاء الطلب
            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'payment_method' => 'cash',
                'status' =>  $validated['total_amount']==$validated['total_paid'] ?  'paid' : 'due',
                'total_amount' => $validated['total_amount'],
                'total_paid' => $validated['total_paid'] ?? 0,
                'date' => $request->date  ?? Carbon::now()->format('Y-m-d'),
                'notes' => $validated['notes'] ?? '',
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
    
            // تسجيل العملية في السجلات
            Log::info('Order created', ['order_id' => $order->id, 'customer_id' => $order->customer_id]);
    
            DB::commit();
    
            // إذا كان الطلب من API، أرجع JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.data_saved_successfully'),
                    'order_id' => $order->id,
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
        $products = Product::select('id', 'name', 'price', 'quantity')
            ->whereNotIn('id', $orderedProductIds)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->price,
                    'max_quantity' => $product->quantity,
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
        ]);
    }
    

    /**
     * Update the specified order in storage.
     */
    public function update(Request $request, Order $order)
    {
        // Validate the request data
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'total_amount' => 'required|numeric|min:0',
            'total_paid' => 'numeric|min:0',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        // Start transaction to ensure data consistency
        DB::beginTransaction();
    
        try {
            // Update the order details
            $order->update([
                'status' =>  $validated['total_amount']==$validated['total_paid'] ?  'paid' : 'due',
                'total_paid' => $validated['total_paid'] ?? 0,
                'customer_id' => $validated['customer_id'],
                'total_amount' => $validated['total_amount'],
                'date' => $request->date  ?? Carbon::now()->format('Y-m-d'),
                'notes' => $validated['notes'] ?? '',
            ]);

            // Loop through each item and update the pivot table with product details
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
    
            
            }

            // Commit the transaction if everything is fine
            DB::commit();

            // Log the order update
            Log::info('Order updated successfully', ['order_id' => $order->id]);
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.data_saved_successfully'),
                    'order_id' => $order->id,
                ], 201);
            }
            // Return with success message
            return redirect()->route('orders.index')
                ->with('success', __('messages.data_updated_successfully'));
    
        } catch (\Exception $e) {
            // Rollback in case of error
            DB::rollBack();
    
            // Log the error
            Log::error('Error updating order', ['error' => $e->getMessage()]);
            if ($request->expectsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
    
            // Return with error message
            return back()->withErrors(['error' => __('messages.error_occurred')]);
        }
    }
    
    
    

    /**
     * Remove the specified order from storage.
     */
    public function destroy(Order $order)
    {
        // Soft delete the order
        $order->delete();

        // Log order deletion
        Log::info('Order deleted', ['order_id' => $order->id]);

        return redirect()->route('orders.index')
            ->with('success', __('messages.data_deleted_successfully'));
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
