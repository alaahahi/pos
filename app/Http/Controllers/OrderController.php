<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use Carbon\Carbon;

class OrderController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:read order', ['only' => ['index']]);
        $this->middleware('permission:create order', ['only' => ['create']]);
        $this->middleware('permission:update order', ['only' => ['update','edit']]);
        $this->middleware('permission:delete order', ['only' => ['destroy']]);
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
        // Start the Order query
        $OrderQuery = Order::with('roles')->latest();

        // Apply the filters if they exist
        $OrderQuery->when($filters['name'], function ($query, $name) {
            return $query->where('name', 'LIKE', "%{$name}%");
        });

        $OrderQuery->when($filters['model'], function ($query, $model) {
            return $query->where('model', 'LIKE', "%{$model}%");
        });


        if (isset($filters['is_active'])) {
            $OrderQuery->where('is_active', $filters['is_active']);
        }
        // Paginate the filtered order
        $order = $OrderQuery->paginate(10);

        return Inertia('Orders/index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'orders' => $order,
        ]);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::pluck('name', 'name')->all();
        return Inertia('Orders/Create', [ 'translations' => __('messages'),'roles' => $roles]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        // Create order instance and assign validated data
        $order = new Order([
            'name' => $request->name,
            'model'=>$request->model,
            'name'=>$request->name,
            'note'=>$request->note,
            'oe_number'=>$request->oe_number,
            'price_cost'=>$request->price_cost,
            'price_with_transport'=>$request->price_with_transport,
            'quantity'=>$request->quantity,
            'selling_price'=>$request->selling_price,
            'situation'=>$request->situation,
            'created' =>Carbon::now()->format('Y-m-d'),
            'image' => $request->image ? $request->image : 'orders/default_order.png',
        ]);
    
        // Handle order upload if a file is provided
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('orders', 'public');
            $order->image = $path;
        }
    
        // Save the order
        $order->save();
    
        // Sync roles if any selected
        if ($request->has('selectedRoles')) {
            $order->syncRoles($request->selectedRoles);
        }
    
        // Redirect with success message
        return redirect()->route('orders.index')
            ->with('success', __('messages.data_saved_successfully'));
    }
    

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        $roles = Role::pluck('name', 'name')->all();
        $orderRoles = $order->roles->pluck('name')->all();
        return Inertia('Orders/Edit', [
            'translations' => __('messages'),
            'order' => $order,
            'roles' => $roles,
            'orderRoles' => $orderRoles
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        // The request is automatically validated using the UpdateOrderRequest rules
        // Check if an avatar file is uploaded and store it
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('orders', 'public');
            $order->image = $path; // Update the order's avatar path
        }
    
        // Update order information, including avatar and other fields, in a single save operation
        $order->update($request->validated());
    
        // Sync roles if any
        //$order->syncRoles($request->selectedRoles);
    
        return redirect()->route('orders.index')
            ->with('success', __('messages.data_updated_successfully'));
    }
    

    public function activate(Order $order)
    {
        $order->update(
            [
                'is_active' => ($order->is_active) ? 0 : 1
            ]
        );
        return redirect()->route('orders.index')
            ->with('success', 'order Status Updated successfully!');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        // تحقق ما إذا كان المنتج محذوفًا بالفعل
        if ($order->trashed()) {
            return redirect()->route('orders.index')
                ->with('error', __('messages.order_already_deleted'));
        }
    
        // حذف المنتج حذفًا ناعمًا
        $order->delete();
    
        return redirect()->route('orders.index')
            ->with('success', __('messages.data_deleted_successfully'));
    }
    public function trashed()
    {
        $trashedOrders = Order::onlyTrashed()->get();
        return view('orders.trashed', compact('trashedOrders'));
    }
    public function restore($id)
    {
        $order = Order::onlyTrashed()->findOrFail($id);
        $order->restore();

        return redirect()->route('orders.index')
            ->with('success', __('messages.order_restored_successfully'));
    }
}
