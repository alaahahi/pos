<?php

namespace App\Http\Controllers;

use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:read system_config');
    }

    public function index()
    {
        $config = SystemConfig::first();
        
        return Inertia::render('SystemConfig/Index', [
            'translations' => __('messages'),
            'config' => $config
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('update system_config');
        
        $validated = $request->validate([
            'first_title_ar' => 'nullable|string|max:255',
            'first_title_kr' => 'nullable|string|max:255',
            'second_title_ar' => 'nullable|string|max:255',
            'second_title_kr' => 'nullable|string|max:255',
            'third_title_ar' => 'nullable|string|max:255',
            'third_title_kr' => 'nullable|string|max:255',
            'exchange_rate' => 'required|numeric|min:0',
            'decoration_types' => 'nullable|array',
            'decoration_types.*.value' => 'required|string',
            'decoration_types.*.label_ar' => 'required|string',
            'decoration_types.*.label_en' => 'nullable|string',
        ]);

        $config = SystemConfig::first();
        
        if ($config) {
            $config->update($validated);
        } else {
            SystemConfig::create($validated);
        }

        return redirect()->back()->with('success', 'تم تحديث الإعدادات بنجاح');
    }
}