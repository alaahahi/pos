<?php

namespace App\Http\Controllers;

use App\Models\Box;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use App\Http\Requests\StoreBoxRequest;
use App\Http\Requests\UpdateBoxRequest;

class BoxesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read boxes', ['only' => ['index']]);
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
        $boxesQuery = Box::latest();

        // Apply the filters if they exist
        $boxesQuery->when($filters['name'], function ($query, $name) {
            return $query->where('name', 'LIKE', "%{$name}%");
        });

        $boxesQuery->when($filters['phone'], function ($query, $phone) {
            return $query->where('phone', 'LIKE', "%{$phone}%");
        });

        if (isset($filters['is_active'])) {
            $boxesQuery->where('is_active', $filters['is_active']);
        }

        // Paginate the filtered boxes
        $boxes = $boxesQuery->paginate(10);

        return Inertia('Boxes/index', [
            'translations' => __('messages'),
            'filters' => $filters,
            'boxes' => $boxes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia('Boxes/Create', [
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

        return redirect()->route('boxes.index')
            ->with('success', __('messages.data_saved_successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Box $box)
    {
        return Inertia('Boxes/Edit', [
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

        return redirect()->route('boxes.index')
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

        return redirect()->route('boxes.index')
            ->with('success', __('messages.status_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Box $box)
    {
        $box->delete();

        return redirect()->route('boxes.index')
            ->with('success', __('messages.data_deleted_successfully'));
    }
}
