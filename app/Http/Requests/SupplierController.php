<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;

class SuppliersController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read suppliers', ['only' => ['index']]);
        $this->middleware('permission:create supplier', ['only' => ['create']]);
        $this->middleware('permission:update supplier', ['only' => ['update', 'edit']]);
        $this->middleware('permission:delete supplier', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Define the filters
        $filters = [
            'name' => $request->name,
            'phone' => $request->phone,
            'is_active' => $request->is_active,
        ];

        // Start the Supplier query
        $suppliersQuery = Supplier::with('balance')->latest();

        // Apply the filters if they exist
        $suppliersQuery->when($filters['name'], function ($query, $name) {
            return $query->where('name', 'LIKE', "%{$name}%");
        });

        $suppliersQuery->when($filters['phone'], function ($query, $phone) {
            return $query->where('phone', 'LIKE', "%{$phone}%");
        });

        if (isset($filters['is_active'])) {
            $suppliersQuery->where('is_active', $filters['is_active']);
        }

        // Paginate the filtered suppliers
        $suppliers = $suppliersQuery->paginate(10);

        return Inertia('Client/index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia('Client/Create', [
            'translations' => __('messages'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        // Create supplier instance and assign validated data
        $supplier = new Supplier($request->validated());
    
        // Handle avatar upload if a file is provided
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $supplier->avatar = $path;
        }
    
        // Save the supplier
        $supplier->save();
    
        // Create a balance record for the supplier
        $supplier->balance()->create([
            'balance_dollar' => 0, // Initial balance in dollars
            'balance_dinar' => 0,  // Initial balance in dinars
            'currency_preference' => 'dinar', // Default preference
            'last_transaction_date' => now(), // Current date
        ]);
    
        return redirect()->route('suppliers.index')
            ->with('success', __('messages.data_saved_successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        return Inertia('Client/Edit', [
            'translations' => __('messages'),
            'supplier' => $supplier,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        // Check if an avatar file is uploaded and store it
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $supplier->avatar = $path;
        }

        // Update supplier information
        $supplier->update($request->validated());

        return redirect()->route('suppliers.index')
            ->with('success', __('messages.data_updated_successfully'));
    }

    /**
     * Activate or deactivate the specified resource.
     */
    public function activate(Supplier $supplier)
    {
        $supplier->update([
            'is_active' => !$supplier->is_active,
        ]);

        return redirect()->route('suppliers.index')
            ->with('success', __('messages.status_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()->route('suppliers.index')
            ->with('success', __('messages.data_deleted_successfully'));
    }
}
