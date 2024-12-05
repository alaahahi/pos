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
        $products = Product::select('id', 'name','selling_price','quantity')->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->selling_price,
                'max_quantity'=> $product->quantity,
            ];
        });

        return Inertia::render('Orders/Create', [
            'translations' => __('messages'),
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(Request $request)
    {
        $order = Order::create([
            'customer_id' => $request->customer_id,
            'order_date' => Carbon::now()->format('Y-m-d'),
            'payment_method'=>'cash',
            'status' => 'pending', // Default status, can be changed later
            'total_amount' => $request->total_amount,
        ]);
        // Attach products with quantities to the order
        foreach ($request->items as $productData) {
            $order->products()->attach($productData['product_id'], [
                'quantity' => $productData['quantity'],
                'price' => $productData['price'],
            ]);
        }

        // Log order creation
        Log::info('Order created', ['order_id' => $order->id, 'customer_id' => $order->customer_id]);

        return redirect()->route('orders.index')
            ->with('success', __('messages.data_saved_successfully'));
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
        $products = Product::select('id', 'name', 'selling_price', 'quantity')
            ->whereNotIn('id', $orderedProductIds)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->selling_price,
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
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);
    
        // Update the order details
        $order->update([
            'customer_id' => $validated['customer_id'],
            'total_amount' => $validated['total_amount'],
        ]);
    
        // Sync the products in the pivot table
        $order->products()->sync(
            collect($validated['items'])->mapWithKeys(function ($item) {
                return [$item['product_id'] => [
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]];
            })->toArray()
        );
    
        // Log the order update
        Log::info('Order updated', ['order_id' => $order->id]);
    
        return redirect()->route('orders.index')
            ->with('success', __('messages.data_updated_successfully'));
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
}
