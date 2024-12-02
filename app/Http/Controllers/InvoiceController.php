<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Order;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
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

        return Inertia::render('Orders/Index', [
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
        $customers = Customer::pluck('name', 'id')->all();
        $products = Product::pluck('name', 'id')->all();

        return Inertia::render('Orders/Create', [
            'translations' => __('messages'),
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created order in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $order = Order::create([
            'customer_id' => $request->customer_id,
            'order_date' => Carbon::now()->format('Y-m-d'),
            'status' => 'pending', // Default status, can be changed later
            'total_amount' => $request->total_amount,
        ]);

        // Attach products with quantities to the order
        foreach ($request->products as $productId => $productData) {
            $order->products()->attach($productId, [
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
        // Get customers and products to populate dropdowns
        $customers = Customer::pluck('name', 'id')->all();
        $products = Product::pluck('name', 'id')->all();
        
        return Inertia::render('Orders/Edit', [
            'translations' => __('messages'),
            'order' => $order,
            'customers' => $customers,
            'products' => $products,
        ]);
    }

    /**
     * Update the specified order in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        // Update the order details
        $order->update($request->validated());

        // Update the products in the pivot table
        $order->products()->sync(
            collect($request->products)->mapWithKeys(function ($productData, $productId) {
                return [$productId => [
                    'quantity' => $productData['quantity'],
                    'price' => $productData['price'],
                ]];
            })
        );

        // Log order update
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
