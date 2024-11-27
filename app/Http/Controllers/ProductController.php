<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use Carbon\Carbon;

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
            'name' => $request->name,
            'model' => $request->model,
            'is_active' => $request->is_active,
        ];
        // Start the Product query
        $ProductQuery = Product::with('roles')->latest();

        // Apply the filters if they exist
        $ProductQuery->when($filters['name'], function ($query, $name) {
            return $query->where('name', 'LIKE', "%{$name}%");
        });

        $ProductQuery->when($filters['model'], function ($query, $model) {
            return $query->where('model', 'LIKE', "%{$model}%");
        });


        if (isset($filters['is_active'])) {
            $ProductQuery->where('is_active', $filters['is_active']);
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
        // Create product instance and assign validated data
        $product = new Product([
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
            'image' => $request->image ? $request->image : 'products/default_product.png',
        ]);
    
        // Handle product upload if a file is provided
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $product->image = $path;
        }
    
        // Save the product
        $product->save();
    
        // Sync roles if any selected
        if ($request->has('selectedRoles')) {
            $product->syncRoles($request->selectedRoles);
        }
    
        // Redirect with success message
        return redirect()->route('products.index')
            ->with('success', __('messages.data_saved_successfully'));
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
        // The request is automatically validated using the UpdateProductRequest rules
        // Check if an avatar file is uploaded and store it
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $product->image = $path; // Update the product's avatar path
        }
    
        // Update product information, including avatar and other fields, in a single save operation
        $product->update($request->validated());
    
        // Sync roles if any
        //$product->syncRoles($request->selectedRoles);
    
        return redirect()->route('products.index')
            ->with('success', __('messages.data_updated_successfully'));
    }
    

    public function activate(Product $product)
    {
        $product->update(
            [
                'is_active' => ($product->is_active) ? 0 : 1
            ]
        );
        return redirect()->route('products.index')
            ->with('success', 'product Status Updated successfully!');
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
        $product->delete();
    
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
}
