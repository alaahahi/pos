<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read product', ['only' => ['index']]);
    }

    public function index(Request $request)
    {
        $query = Vehicle::with([
            'product:id,name,price',
            'purchaseInvoice:id,invoice_number,invoice_date',
            'order:id,date,final_amount,total_amount',
        ])->latest();

        if ($request->filled('status') && in_array($request->status, ['available', 'sold'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('vin', 'like', "%{$search}%")
                  ->orWhere('vehicle_model', 'like', "%{$search}%")
                  ->orWhere('color', 'like', "%{$search}%")
                  ->orWhere('make', 'like', "%{$search}%");
            });
        }

        $vehicles = $query->paginate(20)->withQueryString();

        $stats = [
            'available' => Vehicle::where('status', 'available')->count(),
            'sold' => Vehicle::where('status', 'sold')->count(),
            'total' => Vehicle::count(),
        ];

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'filters' => $request->only(['status', 'search']),
            'stats' => $stats,
            'translations' => __('messages'),
        ]);
    }

    public function findByVin(string $vin)
    {
        $vin = strtoupper(trim($vin));

        if (strlen($vin) < 11) {
            return response()->json(['error' => 'رقم الشانصي قصير جداً'], 422);
        }

        $vehicle = Vehicle::with('product:id,name,price,barcode,model')
            ->available()
            ->where('vin', $vin)
            ->first();

        if (!$vehicle) {
            return response()->json(['error' => 'السيارة غير موجودة أو مباعة مسبقاً'], 404);
        }

        return response()->json([
            'id' => $vehicle->id,
            'vin' => $vehicle->vin,
            'color' => $vehicle->color,
            'vehicle_model' => $vehicle->vehicle_model,
            'make' => $vehicle->make,
            'year' => $vehicle->year,
            'product_id' => $vehicle->product_id,
            'product' => $vehicle->product ? [
                'id' => $vehicle->product->id,
                'name' => $vehicle->product->name,
                'price' => $vehicle->product->price,
                'barcode' => $vehicle->product->barcode,
                'model' => $vehicle->product->model,
            ] : null,
        ]);
    }

    public function search(Request $request)
    {
        $query = strtoupper(trim($request->get('q', '')));

        if (strlen($query) < 3) {
            return response()->json([]);
        }

        $vehicles = Vehicle::with('product:id,name,price')
            ->available()
            ->where(function ($q) use ($query) {
                $q->where('vin', 'like', "%{$query}%")
                  ->orWhere('vehicle_model', 'like', "%{$query}%")
                  ->orWhere('color', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'vin' => $v->vin,
                'color' => $v->color,
                'vehicle_model' => $v->vehicle_model,
                'make' => $v->make,
                'year' => $v->year,
                'product_id' => $v->product_id,
                'product_name' => $v->product?->name,
                'price' => $v->product?->price,
            ]);

        return response()->json($vehicles);
    }
}
