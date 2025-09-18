<?php

namespace App\Http\Controllers;

use App\Services\BarcodeService;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BarcodeController extends Controller
{
    protected $barcodeService;

    public function __construct()
    {
        $this->barcodeService = new BarcodeService();
        $this->middleware('permission:read product', ['only' => ['index', 'show']]);
        $this->middleware('permission:create product', ['only' => ['generate', 'print']]);
    }

    /**
     * Display barcode generation page
     */
    public function index(Request $request)
    {
        $products = Product::where('is_active', 1)
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'LIKE', "%{$search}%")
                           ->orWhere('barcode', 'LIKE', "%{$search}%");
            })
            ->paginate(20);

        return Inertia::render('Barcode/Index', [
            'translations' => __('messages'),
            'products' => $products,
            'supportedTypes' => BarcodeService::getSupportedTypes(),
            'filters' => $request->only(['search'])
        ]);
    }

    /**
     * Generate barcode for a product
     */
    public function generate(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'nullable|string|in:PNG,SVG,JPG',
            'width' => 'nullable|integer|min:1|max:10',
            'height' => 'nullable|integer|min:10|max:200'
        ]);

        $product = Product::findOrFail($request->product_id);
        
        // Generate barcode if product doesn't have one
        if (!$product->barcode) {
            $product->barcode = $this->generateUniqueBarcode();
            $product->save();
        }

        $type = $request->type ?? 'PNG';
        $width = $request->width ?? 2;
        $height = $request->height ?? 30;

        $barcodeService = new BarcodeService($type, $width, $height);
        $result = $barcodeService->generateBarcode($product->barcode);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => __('messages.data_saved_successfully'),
                'barcode' => $result
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to generate barcode: ' . $result['error']
        ], 400, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Print barcode directly to printer
     */
    public function print(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1|max:100',
            'printer_settings' => 'nullable|array'
        ]);

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->quantity ?? 1;
        
        $printerSettings = array_merge([
            'width' => 2,
            'height' => 30,
            'type' => 'PNG'
        ], $request->printer_settings ?? []);

        $barcodeService = new BarcodeService($printerSettings['type'], $printerSettings['width'], $printerSettings['height']);
        
        $results = [];
        for ($i = 0; $i < $quantity; $i++) {
            $result = $barcodeService->generateBarcodeForPrint($product->barcode, $printerSettings);
            $results[] = $result;
        }

        return response()->json([
            'success' => true,
            'message' => "تم إرسال {$quantity} باركود للطباعة",
            'results' => $results,
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => $product->barcode,
                'price' => $product->price
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Batch print multiple products
     */
    public function batchPrint(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'quantity_per_product' => 'nullable|integer|min:1|max:10',
            'printer_settings' => 'nullable|array'
        ]);

        $productIds = $request->product_ids;
        $quantityPerProduct = $request->quantity_per_product ?? 1;
        
        $printerSettings = array_merge([
            'width' => 2,
            'height' => 30,
            'type' => 'PNG'
        ], $request->printer_settings ?? []);

        $barcodeService = new BarcodeService($printerSettings['type'], $printerSettings['width'], $printerSettings['height']);
        
        $results = [];
        $totalBarcodes = 0;

        foreach ($productIds as $productId) {
            $product = Product::findOrFail($productId);
            
            // Generate barcode if product doesn't have one
            if (!$product->barcode) {
                $product->barcode = $this->generateUniqueBarcode();
                $product->save();
            }

            for ($i = 0; $i < $quantityPerProduct; $i++) {
                $result = $barcodeService->generateBarcodeForPrint($product->barcode, $printerSettings);
                $result['product'] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'barcode' => $product->barcode,
                    'price' => $product->price
                ];
                $results[] = $result;
                $totalBarcodes++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "تم إرسال {$totalBarcodes} باركود للطباعة",
            'results' => $results,
            'total_barcodes' => $totalBarcodes
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Download barcode as image
     */
    public function download(Product $product, Request $request)
    {
        $request->validate([
            'type' => 'nullable|string|in:PNG,SVG,JPG',
            'width' => 'nullable|integer|min:1|max:10',
            'height' => 'nullable|integer|min:10|max:200'
        ]);
        
        if (!$product->barcode) {
            $product->barcode = $this->generateUniqueBarcode();
            $product->save();
        }

        $type = $request->type ?? 'PNG';
        $width = $request->width ?? 2;
        $height = $request->height ?? 30;

        $barcodeService = new BarcodeService($type, $width, $height);
        $result = $barcodeService->generateBarcodeBase64($product->barcode);

        if ($result['success']) {
            $filename = "barcode_" . preg_replace('/[^\w\-_\.]/', '_', $product->name) . "_{$product->barcode}." . strtolower($type);
            
            return response(base64_decode($result['data']))
                ->header('Content-Type', $result['mime_type'])
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to generate barcode: ' . $result['error']
        ], 400, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Show barcode preview
     */
    public function preview(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'type' => 'nullable|string|in:PNG,SVG,JPG',
            'width' => 'nullable|integer|min:1|max:10',
            'height' => 'nullable|integer|min:10|max:200'
        ]);

        $type = $request->type ?? 'PNG';
        $width = $request->width ?? 2;
        $height = $request->height ?? 30;

        $barcodeService = new BarcodeService($type, $width, $height);
        $result = $barcodeService->generateBarcodeBase64($request->code);

        if ($result['success']) {
            return response(base64_decode($result['data']))
                ->header('Content-Type', $result['mime_type']);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to generate barcode: ' . $result['error']
        ], 400, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get printer settings
     */
    public function printerSettings()
    {
        return response()->json([
            'success' => true,
            'settings' => [
                'default_width' => 2,
                'default_height' => 30,
                'default_type' => 'PNG',
                'supported_types' => BarcodeService::getSupportedTypes(),
                'width_range' => ['min' => 1, 'max' => 10],
                'height_range' => ['min' => 10, 'max' => 200]
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Generate unique barcode for product
     */
    private function generateUniqueBarcode()
    {
        do {
            $barcode = 'BC' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Product::where('barcode', $barcode)->exists());

        return $barcode;
    }
}
