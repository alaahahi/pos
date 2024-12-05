<?php

namespace App\Http\Controllers;

use App\Models\Box;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Http\Requests\StoreBoxRequest;
use App\Http\Requests\UpdateBoxRequest;

class BoxController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read box', ['only' => ['index']]);
        $this->middleware('permission:create box', ['only' => ['create']]);
        $this->middleware('permission:update box', ['only' => ['update', 'edit']]);
        $this->middleware('permission:delete box', ['only' => ['destroy']]);
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

        // Start the Box query
        $boxsQuery = Box::latest();

        // Apply the filters if they exist
        $boxsQuery->when($filters['name'], function ($query, $name) {
            return $query->where('name', 'LIKE', "%{$name}%");
        });

        $boxsQuery->when($filters['phone'], function ($query, $phone) {
            return $query->where('phone', 'LIKE', "%{$phone}%");
        });

        if (isset($filters['is_active'])) {
            $boxsQuery->where('is_active', $filters['is_active']);
        }

        // Paginate the filtered boxs
        $boxs = $boxsQuery->paginate(10);

        return Inertia('Client/index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'boxs' => $boxs,
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
    public function store(StoreBoxRequest $request)
    {
        // Create box instance and assign validated data
        $box = new Box($request->validated());

        // Handle avatar upload if a file is provided
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $box->avatar = $path;
        }

        // Save the box
        $box->save();

        return redirect()->route('boxs.index')
            ->with('success', __('messages.data_saved_successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Box $box)
    {
        return Inertia('Client/Edit', [
            'translations' => __('messages'),
            'box' => $box,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBoxRequest $request, Box $box)
    {
        // Check if an avatar file is uploaded and store it
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $box->avatar = $path;
        }

        // Update box information
        $box->update($request->validated());

        return redirect()->route('boxs.index')
            ->with('success', __('messages.data_updated_successfully'));
    }

    /**
     * Activate or deactivate the specified resource.
     */
    public function activate(Box $box)
    {
        $box->update([
            'is_active' => !$box->is_active,
        ]);

        return redirect()->route('boxs.index')
            ->with('success', __('messages.status_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Box $box)
    {
        $box->delete();

        return redirect()->route('boxs.index')
            ->with('success', __('messages.data_deleted_successfully'));
    }
}
