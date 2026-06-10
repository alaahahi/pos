<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
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
