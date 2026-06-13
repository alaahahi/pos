<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Services\LogService;

class SuppliersController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read supplier', ['only' => ['index', 'search']]);
        $this->middleware('permission:create supplier', ['only' => ['create']]);
        $this->middleware('permission:update supplier', ['only' => ['update', 'edit']]);
        $this->middleware('permission:delete supplier', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [
            'supplier_id' => $request->supplier_id,
            'phone' => $request->phone,
            'is_active' => $request->is_active,
        ];

        $suppliersQuery = Supplier::with('balance')->latest();

        $suppliersQuery->when($filters['supplier_id'], function ($query, $supplierId) {
            return $query->where('id', $supplierId);
        });

        $suppliersQuery->when($filters['phone'], function ($query, $phone) {
            return $query->where('phone', 'LIKE', "%{$phone}%");
        });

        if ($request->filled('is_active')) {
            $suppliersQuery->where('is_active', $filters['is_active']);
        }

        $suppliers = $suppliersQuery->paginate(10);

        $selectedSupplier = null;
        if ($request->filled('supplier_id')) {
            $selectedSupplier = Supplier::select('id', 'name', 'phone')
                ->find($request->supplier_id);
        }

        return Inertia('Supplier/index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'selectedSupplier' => $selectedSupplier,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * Search suppliers by name or phone (for searchable select filters).
     */
    public function search(Request $request)
    {
        $query = $request->get('q', '');

        $suppliersQuery = Supplier::select('id', 'name', 'phone')
            ->orderBy('name');

        if ($query !== '') {
            $suppliersQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%");
            });
        }

        $suppliers = $suppliersQuery->limit(20)->get();

        return response()->json($suppliers);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia('Supplier/Create', [
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

        // Log supplier creation
        LogService::createLog(
            'Supplier',
            'Created',
            $supplier->id,
            [],
            $supplier->toArray(),
            'success'
        );
    
        // Check if request expects JSON (AJAX)
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __('messages.data_saved_successfully'),
                'supplier' => $supplier,
            ]);
        }
    
        return redirect()->route('suppliers.index')
            ->with('success', __('messages.data_saved_successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Supplier $supplier)
    {
        return Inertia('Supplier/Edit', [
            'translations' => __('messages'),
            'supplier' => $supplier,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $originalData = $supplier->getOriginal();
        // Check if an avatar file is uploaded and store it
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $supplier->avatar = $path;
        }

        // Update supplier information
        $supplier->update($request->validated());

        // Log supplier update
        LogService::createLog(
            'Supplier',
            'Updated',
            $supplier->id,
            $originalData,
            $supplier->toArray(),
            'warning'
        );

        return redirect()->route('suppliers.index')
            ->with('success', __('messages.data_updated_successfully'));
    }

    /**
     * Activate or deactivate the specified resource.
     */
    public function activate(Supplier $supplier)
    {
        $originalData = $supplier->getOriginal();
        $supplier->update([
            'is_active' => !$supplier->is_active,
        ]);

        // Log status toggle
        LogService::createLog(
            'Supplier',
            'Status Toggled',
            $supplier->id,
            $originalData,
            $supplier->toArray(),
            'info'
        );

        return redirect()->route('suppliers.index')
            ->with('success', __('messages.status_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        $originalData = $supplier->toArray();
        $id = $supplier->id;
        $supplier->delete();

        // Log deletion
        LogService::createLog(
            'Supplier',
            'Deleted',
            $id,
            $originalData,
            [],
            'danger'
        );

        return redirect()->route('suppliers.index')
            ->with('success', __('messages.data_deleted_successfully'));
    }
}

