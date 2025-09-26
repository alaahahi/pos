<?php

namespace App\Http\Controllers;

use App\Models\Decoration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicGalleryController extends Controller
{
    /**
     * Display the public gallery
     */
    public function index(Request $request)
    {
        $query = Decoration::active()->with('orders');

        // Filter by type
        if ($request->type) {
            $query->byType($request->type);
        }

        // Filter featured
        if ($request->featured) {
            $query->featured();
        }

        // Search
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'LIKE', "%{$request->search}%")
                  ->orWhere('description', 'LIKE', "%{$request->search}%");
            });
        }

        $decorations = $query->orderBy('is_featured', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->paginate(12);

        // Get decoration types for filter
        $types = Decoration::active()
            ->select('type')
            ->distinct()
            ->get()
            ->map(function ($decoration) {
                return [
                    'value' => $decoration->type,
                    'label' => $decoration->type_name
                ];
            });

        // Get company info from environment
        $companyInfo = [
            'name' => env('COMPANY_NAME', 'نظام إدارة الديكورات'),
            'phone' => env('COMPANY_PHONE', ''),
            'email' => env('COMPANY_EMAIL', ''),
            'address' => env('COMPANY_ADDRESS', ''),
            'logo' => env('COMPANY_LOGO', 'dashboard-assets/img/logo.png'),
            'primary_color' => env('PRIMARY_COLOR', '#007bff'),
            'primary_color_dark' => env('PRIMARY_COLOR_DARK', '#0056b3')
        ];

        return Inertia::render('Public/Gallery', [
            'decorations' => $decorations,
            'types' => $types,
            'companyInfo' => $companyInfo,
            'filters' => $request->only(['type', 'featured', 'search'])
        ]);
    }

    /**
     * Display a single decoration
     */
    public function show(Decoration $decoration)
    {
        if (!$decoration->is_active) {
            abort(404);
        }

        // Get related decorations
        $relatedDecorations = Decoration::active()
            ->where('type', $decoration->type)
            ->where('id', '!=', $decoration->id)
            ->limit(4)
            ->get();

        // Get company info
        $companyInfo = [
            'name' => env('COMPANY_NAME', 'نظام إدارة الديكورات'),
            'phone' => env('COMPANY_PHONE', ''),
            'email' => env('COMPANY_EMAIL', ''),
            'address' => env('COMPANY_ADDRESS', ''),
            'logo' => env('COMPANY_LOGO', 'dashboard-assets/img/logo.png'),
            'primary_color' => env('PRIMARY_COLOR', '#007bff'),
            'primary_color_dark' => env('PRIMARY_COLOR_DARK', '#0056b3')
        ];

        return Inertia::render('Public/DecorationDetail', [
            'decoration' => $decoration,
            'relatedDecorations' => $relatedDecorations,
            'companyInfo' => $companyInfo
        ]);
    }

    /**
     * Get decorations for API
     */
    public function api(Request $request)
    {
        $query = Decoration::active();

        if ($request->type) {
            $query->byType($request->type);
        }

        if ($request->featured) {
            $query->featured();
        }

        $decorations = $query->orderBy('is_featured', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->paginate($request->per_page ?? 12);

        return response()->json($decorations);
    }
}