<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Services\LogService;

class CustomersController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read customers', ['only' => ['index']]);
        $this->middleware('permission:create customer', ['only' => ['create']]);
        $this->middleware('permission:update customer', ['only' => ['update', 'edit']]);
        $this->middleware('permission:delete customer', ['only' => ['destroy']]);
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

        // Start the Customer query
        $customersQuery = Customer::with('balance')->latest();

        // Apply the filters if they exist
        $customersQuery->when($filters['name'], function ($query, $name) {
            return $query->where('name', 'LIKE', "%{$name}%");
        });

        $customersQuery->when($filters['phone'], function ($query, $phone) {
            return $query->where('phone', 'LIKE', "%{$phone}%");
        });

        if (isset($filters['is_active'])) {
            $customersQuery->where('is_active', $filters['is_active']);
        }

        // Paginate the filtered customers
        $customers = $customersQuery->paginate(10);

        return Inertia('Client/index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'customers' => $customers,
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
    public function store(StoreCustomerRequest $request)
    {
        // Create customer instance and assign validated data
        $customer = new Customer($request->validated());
    
        // Handle avatar upload if a file is provided
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $customer->avatar = $path;
        }
    
        // Save the customer
        $customer->save();
    
        // Create a balance record for the customer
        $customer->balance()->create([
            'balance_dollar' => 0, // Initial balance in dollars
            'balance_dinar' => 0,  // Initial balance in dinars
            'currency_preference' => 'dinar', // Default preference
            'last_transaction_date' => now(), // Current date
        ]);

        // Log customer creation
        LogService::createLog(
            'Customer',
            'Created',
            $customer->id,
            [],
            $customer->toArray(),
            'success'
        );
    
        return redirect()->route('customers.index')
            ->with('success', __('messages.data_saved_successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return Inertia('Client/Edit', [
            'translations' => __('messages'),
            'customer' => $customer,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $originalData = $customer->getOriginal();
        // Check if an avatar file is uploaded and store it
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $customer->avatar = $path;
        }

        // Update customer information
        $customer->update($request->validated());

        // Log customer update
        LogService::createLog(
            'Customer',
            'Updated',
            $customer->id,
            $originalData,
            $customer->toArray(),
            'warning'
        );

        return redirect()->route('customers.index')
            ->with('success', __('messages.data_updated_successfully'));
    }

    /**
     * Activate or deactivate the specified resource.
     */
    public function activate(Customer $customer)
    {
        $originalData = $customer->getOriginal();
        $customer->update([
            'is_active' => !$customer->is_active,
        ]);

        // Log status toggle
        LogService::createLog(
            'Customer',
            'Status Toggled',
            $customer->id,
            $originalData,
            $customer->toArray(),
            'info'
        );

        return redirect()->route('customers.index')
            ->with('success', __('messages.status_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $originalData = $customer->toArray();
        $id = $customer->id;
        $customer->delete();

        // Log deletion
        LogService::createLog(
            'Customer',
            'Deleted',
            $id,
            $originalData,
            [],
            'danger'
        );

        return redirect()->route('customers.index')
            ->with('success', __('messages.data_deleted_successfully'));
    }
}
