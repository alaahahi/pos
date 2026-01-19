<?php

namespace App\Http\Controllers;

use App\Models\Decoration;
use App\Models\DecorationOrder;
use App\Models\Customer;
use App\Models\CustomerBalance;
use App\Models\Box;
use App\Models\SystemConfig;
use App\Models\MonthlyAccount;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\EmployeeCommission;
use App\Http\Controllers\AccountingController;

class DecorationController extends Controller
{
    protected $accountingController;
    protected $mainBox;
    protected $defaultCurrency;

    public function __construct(AccountingController $accountingController)
    {
        $this->accountingController = $accountingController;
        
        // Middleware for permission handling
        $this->middleware('permission:read decoration', ['only' => ['index', 'show', 'dashboard', 'orders', 'simpleOrders', 'showOrder', 'myOrders']]);
        $this->middleware('permission:create decoration', ['only' => ['create', 'store', 'createOrder']]);
        $this->middleware('permission:update decoration', ['only' => ['edit', 'update', 'updateOrderStatus']]);
        $this->middleware('permission:delete decoration', ['only' => ['destroy']]);
        
        // Get main box
        try {
            $userAccount = UserType::where('name', 'account')->first()?->id;
            $this->mainBox = User::with('wallet')
                ->where('type_id', $userAccount)
                ->where('email', 'mainBox@account.com')
                ->first();
        } catch (\Exception $e) {
            $this->mainBox = null;
        }
        
        $this->defaultCurrency = env('DEFAULT_CURRENCY', 'IQD');
    }

    /**
     * Display decoration dashboard with tabs
     */
    public function dashboard(Request $request)
    {
        $activeTab = $request->get('tab', 'decorations');
        
        // Get decorations
        $decorations = Decoration::query()
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'LIKE', "%{$search}%")
                           ->orWhere('description', 'LIKE', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($request->currency, function ($query, $currency) {
                return $query->where('currency', $currency);
            })
            ->when($request->is_active !== null, function ($query) use ($request) {
                return $query->where('is_active', $request->is_active);
            })
            ->latest()
            ->paginate(12);

        // Get orders
        $orders = DecorationOrder::with(['decoration', 'customer', 'assignedEmployee'])
            ->when($request->search, function ($query, $search) {
                return $query->where('customer_name', 'LIKE', "%{$search}%")
                           ->orWhere('customer_phone', 'LIKE', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->assigned_employee_id, function ($query, $employeeId) {
                return $query->where('assigned_employee_id', $employeeId);
            })
            ->when($request->event_date, function ($query, $date) {
                return $query->whereDate('event_date', $date);
            })
            ->latest()
            ->paginate(15);

        // Get customers and employees
        $customers = \App\Models\Customer::where('is_active', true)
            ->select('id', 'name', 'phone', 'email', 'address')
            ->orderBy('name')
            ->get();

        $employees = \App\Models\User::where('is_active', true)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        // Analytics
        $analytics = [
            'total_decorations' => Decoration::count(),
            'active_decorations' => Decoration::where('is_active', true)->count(),
            'total_orders' => DecorationOrder::count(),
            'pending_orders' => DecorationOrder::where('status', 'created')->count(),
            'completed_orders' => DecorationOrder::where('status', 'completed')->count(),
            'total_revenue' => DecorationOrder::whereIn('status', ['full_payment', 'completed'])->sum('total_price'),
            'monthly_orders' => DecorationOrder::whereMonth('created_at', now()->month)->count(),
            'orders_by_status' => DecorationOrder::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray()
        ];

        // Get system config for exchange rate and decoration types
        $systemConfig = SystemConfig::first();
        $decorationTypes = $systemConfig->decoration_types ?? [
            ['value' => 'birthday', 'label_ar' => 'عيد ميلاد', 'label_en' => 'Birthday'],
            ['value' => 'gender_reveal', 'label_ar' => 'تحديد جنس المولود', 'label_en' => 'Gender Reveal'],
            ['value' => 'baby_shower', 'label_ar' => 'حفلة الولادة', 'label_en' => 'Baby Shower'],
            ['value' => 'wedding', 'label_ar' => 'زفاف', 'label_en' => 'Wedding'],
            ['value' => 'graduation', 'label_ar' => 'تخرج', 'label_en' => 'Graduation'],
            ['value' => 'corporate', 'label_ar' => 'شركات', 'label_en' => 'Corporate'],
            ['value' => 'religious', 'label_ar' => 'ديني', 'label_en' => 'Religious'],
            ['value' => 'other', 'label_ar' => 'أخرى', 'label_en' => 'Other'],
        ];
        
        return Inertia::render('Decorations/Dashboard', [
            'translations' => __('messages'),
            'decorations' => $decorations,
            'orders' => $orders,
            'customers' => $customers,
            'employees' => $employees,
            'analytics' => $analytics,
            'activeTabProp' => $activeTab,
            'exchangeRate' => $systemConfig ? $systemConfig->exchange_rate : 1500,
            'decorationTypes' => $decorationTypes,
            'filters' => $request->only(['search', 'type', 'currency', 'is_active', 'status', 'assigned_employee_id', 'event_date'])
        ]);
    }

    /**
     * Display a listing of decorations (legacy)
     */
    public function index(Request $request)
    {
        $decorations = Decoration::query()
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'LIKE', "%{$search}%")
                           ->orWhere('description', 'LIKE', "%{$search}%");
            })
            ->when($request->type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($request->is_active !== null, function ($query, $is_active) {
                return $query->where('is_active', $is_active);
            })
            ->latest()
            ->paginate(12);

        $customers = \App\Models\Customer::where('is_active', true)
            ->select('id', 'name', 'phone', 'email', 'address')
            ->orderBy('name')
            ->get();

        // Get system config for decoration types
        $systemConfig = SystemConfig::first();
        $decorationTypes = $systemConfig->decoration_types ?? [
            ['value' => 'birthday', 'label_ar' => 'عيد ميلاد', 'label_en' => 'Birthday'],
            ['value' => 'gender_reveal', 'label_ar' => 'تحديد جنس المولود', 'label_en' => 'Gender Reveal'],
            ['value' => 'baby_shower', 'label_ar' => 'حفلة الولادة', 'label_en' => 'Baby Shower'],
            ['value' => 'wedding', 'label_ar' => 'زفاف', 'label_en' => 'Wedding'],
            ['value' => 'graduation', 'label_ar' => 'تخرج', 'label_en' => 'Graduation'],
            ['value' => 'corporate', 'label_ar' => 'شركات', 'label_en' => 'Corporate'],
            ['value' => 'religious', 'label_ar' => 'ديني', 'label_en' => 'Religious'],
            ['value' => 'other', 'label_ar' => 'أخرى', 'label_en' => 'Other'],
        ];

        return Inertia::render('Decorations/Index', [
            'translations' => __('messages'),
            'decorations' => $decorations,
            'customers' => $customers,
            'filters' => $request->only(['search', 'type', 'is_active']),
            'decorationTypes' => $decorationTypes
        ]);
    }

    /**
     * Show the form for creating a new decoration
     */
    public function create()
    {
        // Get system config for exchange rate and decoration types
        $systemConfig = SystemConfig::first();
        // Get decoration types from system config or use defaults
        $decorationTypes = $systemConfig->decoration_types ?? [
            ['value' => 'birthday', 'label_ar' => 'عيد ميلاد', 'label_en' => 'Birthday'],
            ['value' => 'gender_reveal', 'label_ar' => 'تحديد جنس المولود', 'label_en' => 'Gender Reveal'],
            ['value' => 'baby_shower', 'label_ar' => 'حفلة الولادة', 'label_en' => 'Baby Shower'],
            ['value' => 'wedding', 'label_ar' => 'زفاف', 'label_en' => 'Wedding'],
            ['value' => 'graduation', 'label_ar' => 'تخرج', 'label_en' => 'Graduation'],
            ['value' => 'corporate', 'label_ar' => 'شركات', 'label_en' => 'Corporate'],
            ['value' => 'religious', 'label_ar' => 'ديني', 'label_en' => 'Religious'],
            ['value' => 'other', 'label_ar' => 'أخرى', 'label_en' => 'Other'],
        ];
        
        return Inertia::render('Decorations/Create', [
            'decorationTypes' => $decorationTypes,
            'translations' => [
                'decorations' => 'الديكورات',
                'create_decoration' => 'إضافة ديكور جديد',
                'decoration_name' => 'اسم الديكور',
                'decoration_description' => 'وصف الديكور',
                'decoration_type' => 'نوع الديكور',
                'currency' => 'العملة',
                'base_price' => 'السعر الأساسي',
                'duration_hours' => 'مدة التنفيذ (ساعات)',
                'team_size' => 'حجم الفريق',
                'active' => 'نشط',
                'save' => 'حفظ',
                'cancel' => 'إلغاء',
                'dinar' => 'دينار',
                'dollar' => 'دولار',
                'birthday' => 'عيد ميلاد',
                'gender_reveal' => 'تحديد جنس المولود',
                'baby_shower' => 'حفلة الولادة',
                'wedding' => 'زفاف',
                'graduation' => 'تخرج',
                'corporate' => 'شركات',
                'religious' => 'ديني'
            ],
            'exchangeRate' => $systemConfig ? $systemConfig->exchange_rate : 1500
        ]);
    }

    /**
     * Store a newly created decoration
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:birthday,gender_reveal,baby_shower,wedding,graduation,corporate,religious',
            'currency' => 'required|in:dinar,dollar',
            'base_price' => 'required|numeric|min:0',
            'duration_hours' => 'required|integer|min:1',
            'team_size' => 'required|integer|min:1',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'youtube_links' => 'nullable|array|max:3',
            'youtube_links.*' => 'nullable|url',
            'video_url' => 'nullable|url',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'materials_cost' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
            'transportation_cost' => 'nullable|numeric|min:0'
        ]);

        $data = $request->only([
            'name', 'description', 'type', 'currency', 'base_price', 
            'duration_hours', 'team_size', 'video_url', 'is_active', 'is_featured'
        ]);

        // Get exchange rate from system config
        $systemConfig = SystemConfig::first();
        $exchangeRate = $systemConfig ? $systemConfig->exchange_rate : 1500;
        $basePrice = floatval($request->base_price);
        
        // Calculate prices in both currencies based on exchange rate
        if ($request->currency === 'dinar') {
            $data['base_price_dinar'] = $basePrice;
            $data['base_price_dollar'] = round($basePrice / $exchangeRate, 2);
        } else {
            $data['base_price_dollar'] = $basePrice;
            $data['base_price_dinar'] = round($basePrice * $exchangeRate, 2);
        }

        // Handle main image
        if ($request->hasFile('main_image')) {
            $data['image'] = $request->file('main_image')->store('decorations', 'public');
        }

        // Handle additional images
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('decorations', 'public');
            }
            $data['images'] = $images;
            
            // Set first image as thumbnail if no main image
            if (!empty($images) && !isset($data['image'])) {
                $data['thumbnail'] = $images[0];
            }
        }

        // Handle YouTube links
        if ($request->has('youtube_links')) {
            $youtubeLinks = array_filter($request->youtube_links, function($link) {
                return !empty($link) && (strpos($link, 'youtube.com') !== false || strpos($link, 'youtu.be') !== false);
            });
            $data['youtube_links'] = array_values($youtubeLinks);
        }

        // Set pricing details
        $data['pricing_details'] = [
            'materials' => $request->materials_cost ?? 0,
            'labor' => $request->labor_cost ?? 0,
            'transportation' => $request->transportation_cost ?? 0
        ];

        $decoration = Decoration::create($data);

        // Log the decoration creation
        \App\Models\Log::create([
            'module_name' => 'Decoration',
            'action' => 'Created',
            'badge' => 'success',
            'affected_record_id' => $decoration->id,
            'original_data' => json_encode([]),
            'updated_data' => json_encode($decoration->toArray()),
            'by_user_id' => auth()->user()->id,
        ]);

        return redirect()->route('decorations.dashboard')
            ->with('success', 'تم إنشاء الديكور بنجاح');
    }

    /**
     * Display the specified decoration
     */
    public function show(Decoration $decoration)
    {
        return Inertia::render('Decorations/Show', [
            'translations' => __('messages'),
            'decoration' => $decoration->load('orders')
        ]);
    }

    /**
     * Show the form for editing the specified decoration
     */
    public function edit(Decoration $decoration)
    {
        // Debug: Log decoration data
        \Log::info('Decoration edit - youtube_links:', ['youtube_links' => $decoration->youtube_links]);
        \Log::info('Decoration edit - video_url:', ['video_url' => $decoration->video_url]);
        
        // Get decoration types from system config
        $systemConfig = SystemConfig::first();
        $decorationTypes = $systemConfig->decoration_types ?? [
            ['value' => 'birthday', 'label_ar' => 'عيد ميلاد', 'label_en' => 'Birthday'],
            ['value' => 'gender_reveal', 'label_ar' => 'تحديد جنس المولود', 'label_en' => 'Gender Reveal'],
            ['value' => 'baby_shower', 'label_ar' => 'حفلة الولادة', 'label_en' => 'Baby Shower'],
            ['value' => 'wedding', 'label_ar' => 'زفاف', 'label_en' => 'Wedding'],
            ['value' => 'graduation', 'label_ar' => 'تخرج', 'label_en' => 'Graduation'],
            ['value' => 'corporate', 'label_ar' => 'شركات', 'label_en' => 'Corporate'],
            ['value' => 'religious', 'label_ar' => 'ديني', 'label_en' => 'Religious'],
            ['value' => 'other', 'label_ar' => 'أخرى', 'label_en' => 'Other'],
        ];
        
        return Inertia::render('Decorations/Edit', [
            'decoration' => $decoration,
            'decorationTypes' => $decorationTypes,
            'translations' => [
                'edit_decoration' => 'تعديل الديكور',
                'basic_information' => 'المعلومات الأساسية',
                'pricing_details' => 'تفاصيل الأسعار',
                'additional_details' => 'تفاصيل إضافية',
                'media_upload' => 'رفع الوسائط',
                'decoration_name' => 'اسم الديكور',
                'decoration_description' => 'وصف الديكور',
                'decoration_type' => 'نوع الديكور',
                'currency' => 'العملة',
                'base_price' => 'السعر الأساسي',
                'duration_hours' => 'مدة التنفيذ (ساعات)',
                'team_size' => 'حجم الفريق',
                'materials_cost' => 'تكلفة المواد',
                'labor_cost' => 'تكلفة العمالة',
                'transportation_cost' => 'تكلفة النقل',
                'included_items' => 'العناصر المشمولة',
                'optional_items' => 'العناصر الاختيارية',
                'status' => 'الحالة',
                'featured' => 'مميز',
                'main_image' => 'الصورة الرئيسية',
                'additional_images' => 'صور إضافية',
                'video_upload' => 'رفع الفيديو',
                'video_url_alternative' => 'رابط الفيديو (بديل)',
                'media_summary' => 'ملخص الوسائط',
                'images' => 'صور',
                'video' => 'فيديو',
                'uploaded' => 'تم الرفع',
                'not_uploaded' => 'لم يتم الرفع',
                'active' => 'نشط',
                'inactive' => 'غير نشط',
                'featured' => 'مميز',
                'not_featured' => 'غير مميز',
                'previous' => 'السابق',
                'next' => 'التالي',
                'update' => 'تحديث',
                'updating' => 'جاري التحديث...',
                'enter_decoration_name' => 'أدخل اسم الديكور',
                'select_type' => 'اختر النوع',
                'enter_description' => 'أدخل وصف الديكور',
                'enter_duration' => 'أدخل المدة',
                'enter_team_size' => 'أدخل حجم الفريق',
                'enter_base_price' => 'أدخل السعر الأساسي',
                'enter_price_dinar' => 'أدخل السعر بالدينار',
                'enter_price_dollar' => 'أدخل السعر بالدولار',
                'enter_materials_cost' => 'أدخل تكلفة المواد',
                'enter_labor_cost' => 'أدخل تكلفة العمالة',
                'enter_transportation_cost' => 'أدخل تكلفة النقل',
                'enter_included_items' => 'أدخل العناصر المشمولة (كل عنصر في سطر)',
                'enter_optional_items' => 'أدخل العناصر الاختيارية (كل عنصر في سطر)',
                'enter_video_url' => 'أدخل رابط الفيديو',
                'click_or_drag_image' => 'انقر أو اسحب الصورة هنا',
                'click_or_drag_multiple_images' => 'انقر أو اسحب الصور هنا',
                'click_or_drag_video' => 'انقر أو اسحب الفيديو هنا',
                'supported_formats' => 'الصيغ المدعومة: JPG, PNG, GIF, WebP',
                'supported_video_formats' => 'الصيغ المدعومة: MP4, AVI, MOV, WMV, FLV, WebM',
                'max_images_limit' => 'الحد الأقصى: 10 صور',
                'or_enter_video_url' => 'أو أدخل رابط فيديو من YouTube أو Vimeo',
                'included_items_help' => 'اكتب كل عنصر في سطر منفصل',
                'optional_items_help' => 'اكتب كل عنصر في سطر منفصل',
                'existing_video' => 'فيديو موجود',
                'new_image' => 'صورة جديدة',
                'image' => 'صورة',
                'dinar' => 'دينار',
                'dollar' => 'دولار',
                'birthday' => 'عيد ميلاد',
                'gender_reveal' => 'تحديد جنس المولود',
                'baby_shower' => 'حفلة الولادة',
                'wedding' => 'زفاف',
                'graduation' => 'تخرج',
                'corporate' => 'شركات',
                'religious' => 'ديني'
            ]
        ]);
    }

    /**
     * Update the specified decoration (POST method)
     */
    public function updatePost(Request $request, Decoration $decoration)
    {
        // Debug: Log incoming request data
        \Log::info('Decoration update POST request data:', $request->all());
        
        // If only currency is being updated
        if ($request->has('currency') && count($request->all()) === 1) {
            $request->validate([
                'currency' => 'required|in:dinar,dollar'
            ]);
            
            // Update currency and set base_price to match the selected currency
            $newCurrency = $request->currency;
            $basePrice = $newCurrency === 'dollar' 
                ? $decoration->base_price_dollar 
                : $decoration->base_price_dinar;
            
            $decoration->update([
                'currency' => $newCurrency,
                'base_price' => $basePrice
            ]);
            
            // Return redirect for Inertia compatibility
            return redirect()->back()->with('success', 'تم تحديث العملة بنجاح');
        }

        // Full update validation
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:birthday,gender_reveal,baby_shower,wedding,graduation,corporate,religious',
            'currency' => 'required|in:dinar,dollar',
            'base_price' => 'required|numeric|min:0',
            'base_price_dinar' => 'required|numeric|min:0',
            'base_price_dollar' => 'required|numeric|min:0',
            'duration_hours' => 'required|integer|min:1',
            'team_size' => 'required|integer|min:1',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'youtube_links' => 'nullable|array|max:3',
            'youtube_links.*' => 'nullable|url',
            'video_url' => 'nullable|url',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'materials_cost' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
            'transportation_cost' => 'nullable|numeric|min:0'
        ]);

        $data = $request->only([
            'name', 'description', 'type', 'currency', 'base_price', 'base_price_dinar', 'base_price_dollar',
            'duration_hours', 'team_size', 'video_url', 'is_active', 'is_featured'
        ]);

        // Handle main image
        if ($request->hasFile('main_image')) {
            // Delete old main image
            if ($decoration->image) {
                Storage::disk('public')->delete($decoration->image);
            }
            $data['image'] = $request->file('main_image')->store('decorations', 'public');
        }

        // Handle additional images
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('decorations', 'public');
            }
            
            // Merge with existing images
            $existingImages = $decoration->images ?? [];
            $data['images'] = array_merge($existingImages, $images);
            
            // Set first image as thumbnail if no main image
            if (!isset($data['image']) && !empty($data['images'])) {
                $data['thumbnail'] = $data['images'][0];
            }
        }

        // Handle YouTube links
        if ($request->has('youtube_links')) {
            $youtubeLinks = array_filter($request->youtube_links, function($link) {
                return !empty($link) && (strpos($link, 'youtube.com') !== false || strpos($link, 'youtu.be') !== false);
            });
            $data['youtube_links'] = array_values($youtubeLinks);
            \Log::info('YouTube links processed:', ['links' => $data['youtube_links']]);
        }

        // Handle pricing details
        $pricingDetails = [];
        if ($request->has('materials_cost')) {
            $pricingDetails['materials'] = $request->materials_cost;
        }
        if ($request->has('labor_cost')) {
            $pricingDetails['labor'] = $request->labor_cost;
        }
        if ($request->has('transportation_cost')) {
            $pricingDetails['transportation'] = $request->transportation_cost;
        }
        if (!empty($pricingDetails)) {
            $data['pricing_details'] = $pricingDetails;
        }

        // Handle included items
        if ($request->has('included_items_text')) {
            $includedItems = array_filter(array_map('trim', explode("\n", $request->included_items_text)));
            $data['included_items'] = $includedItems;
        }

        // Handle optional items
        if ($request->has('optional_items_text')) {
            $optionalItems = array_filter(array_map('trim', explode("\n", $request->optional_items_text)));
            $data['optional_items'] = $optionalItems;
        }

        \Log::info('Data being updated:', $data);
        $decoration->update($data);
        \Log::info('Decoration updated successfully. Video file path:', ['path' => $decoration->video_file]);

        // Log the update
        \App\Models\Log::create([
            'module_name' => 'Decoration',
            'action' => 'Updated',
            'badge' => 'info',
            'affected_record_id' => $decoration->id,
            'original_data' => json_encode([]),
            'updated_data' => json_encode($decoration->toArray()),
            'by_user_id' => auth()->user()->id,
        ]);

        return redirect()->route('decorations.dashboard')
            ->with('success', 'تم تحديث الديكور بنجاح');
    }

    /**
     * Update the specified decoration (PATCH method)
     */
    public function update(Request $request, Decoration $decoration)
    {
        // Debug: Log incoming request data
        \Log::info('Decoration update request data:', $request->all());
        
        // If only currency is being updated
        if ($request->has('currency') && count($request->all()) === 1) {
            $request->validate([
                'currency' => 'required|in:dinar,dollar'
            ]);
            
            // Update currency and set base_price to match the selected currency
            $newCurrency = $request->currency;
            $basePrice = $newCurrency === 'dollar' 
                ? $decoration->base_price_dollar 
                : $decoration->base_price_dinar;
            
            $decoration->update([
                'currency' => $newCurrency,
                'base_price' => $basePrice
            ]);
            
            // Return redirect for Inertia compatibility
            return redirect()->back()->with('success', 'تم تحديث العملة بنجاح');
        }

        // Full update validation
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:birthday,gender_reveal,baby_shower,wedding,graduation,corporate,religious',
            'currency' => 'required|in:dinar,dollar',
            'base_price' => 'required|numeric|min:0',
            'base_price_dinar' => 'required|numeric|min:0',
            'base_price_dollar' => 'required|numeric|min:0',
            'duration_hours' => 'required|integer|min:1',
            'team_size' => 'required|integer|min:1',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'youtube_links' => 'nullable|array|max:3',
            'youtube_links.*' => 'nullable|url',
            'video_url' => 'nullable|url',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'materials_cost' => 'nullable|numeric|min:0',
            'labor_cost' => 'nullable|numeric|min:0',
            'transportation_cost' => 'nullable|numeric|min:0'
        ]);

        $data = $request->only([
            'name', 'description', 'type', 'currency', 'base_price', 'base_price_dinar', 'base_price_dollar',
            'duration_hours', 'team_size', 'video_url', 'is_active', 'is_featured'
        ]);

        // Handle main image
        if ($request->hasFile('main_image')) {
            // Delete old main image
            if ($decoration->image) {
                Storage::disk('public')->delete($decoration->image);
            }
            $data['image'] = $request->file('main_image')->store('decorations', 'public');
        }

        // Handle additional images
        if ($request->hasFile('images')) {
            $images = [];
            foreach ($request->file('images') as $image) {
                $images[] = $image->store('decorations', 'public');
            }
            
            // Merge with existing images
            $existingImages = $decoration->images ?? [];
            $data['images'] = array_merge($existingImages, $images);
            
            // Set first image as thumbnail if no main image
            if (!isset($data['image']) && !empty($data['images'])) {
                $data['thumbnail'] = $data['images'][0];
            }
        }

        // Handle YouTube links
        if ($request->has('youtube_links')) {
            $youtubeLinks = array_filter($request->youtube_links, function($link) {
                return !empty($link) && (strpos($link, 'youtube.com') !== false || strpos($link, 'youtu.be') !== false);
            });
            $data['youtube_links'] = array_values($youtubeLinks);
        }

        // Set pricing details
        $data['pricing_details'] = [
            'materials' => $request->materials_cost ?? 0,
            'labor' => $request->labor_cost ?? 0,
            'transportation' => $request->transportation_cost ?? 0
        ];

        // Handle included and optional items
        if ($request->included_items_text) {
            $data['included_items'] = array_filter(array_map('trim', explode("\n", $request->included_items_text)));
        }
        
        if ($request->optional_items_text) {
            $data['optional_items'] = array_filter(array_map('trim', explode("\n", $request->optional_items_text)));
        }

        $decoration->update($data);

        return redirect()->route('decorations.dashboard')
            ->with('success', 'تم تحديث الديكور بنجاح');
    }

    /**
     * Remove the specified decoration
     */
    public function destroy(Decoration $decoration)
    {
        if ($decoration->image) {
            Storage::disk('public')->delete($decoration->image);
        }

        $decoration->delete();

        return redirect()->route('decorations.dashboard')
            ->with('success', 'تم حذف الديكور بنجاح');
    }

    /**
     * Display decoration orders
     */
    public function orders(Request $request)
    {
        $orders = DecorationOrder::with(['decoration', 'assignedEmployee', 'assignedTeam', 'customer'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where('customer_name', 'LIKE', "%{$search}%")
                           ->orWhere('customer_phone', 'LIKE', "%{$search}%");
            })
            ->latest()
            ->paginate(15);

        // Get employees list for assignment
        $employees = \App\Models\User::where('is_active', true)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Decorations/Orders', [
            'translations' => __('messages'),
            'orders' => $orders,
            'employees' => $employees,
            'filters' => $request->only(['status', 'search'])
        ]);
    }

    /**
     * Display simple decoration orders (Excel-like view)
     */
    public function simpleOrders(Request $request)
    {
        $orders = DecorationOrder::with(['decoration', 'assignedEmployee', 'customer'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->employee, function ($query, $employeeId) {
                return $query->where('assigned_employee_id', $employeeId);
            })
            ->when($request->search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'LIKE', "%{$search}%")
                      ->orWhere('customer_phone', 'LIKE', "%{$search}%")
                      ->orWhereHas('decoration', function ($decorationQuery) use ($search) {
                          $decorationQuery->where('name', 'LIKE', "%{$search}%");
                      });
                });
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                return $query->whereDate('event_date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                return $query->whereDate('event_date', '<=', $dateTo);
            })
            ->latest()
            ->paginate(20);

        // Get employees list for filters
        $employees = \App\Models\User::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Decorations/SimpleOrders', [
            'translations' => __('messages'),
            'orders' => $orders,
            'employees' => $employees,
            'filters' => $request->only(['status', 'search', 'employee', 'date_from', 'date_to'])
        ]);
    }

    /**
     * Show form for creating new decoration order
     */
    public function createOrderForm()
    {
        // التحقق من الصلاحيات
        if (!auth()->user()->can('create decoration')) {
            abort(403, 'ليس لديك صلاحية لإنشاء طلبات الديكورات');
        }

        // Redirect to decorations index page where user can create order
        return redirect()->route('decorations.index')
            ->with('openCreateModal', true);
    }

    /**
     * Create new decoration order
     */
    public function createOrder(Request $request)
    {
        // التحقق من الصلاحيات
        if (!auth()->user()->can('create decoration')) {
            return redirect()->back()
                ->with('error', 'ليس لديك صلاحية لإنشاء طلبات الديكورات')
                ->withInput();
        }

        try {
            $request->validate([
                'decoration_id' => 'nullable|exists:decorations,id',
                'decoration_name' => 'nullable|string|max:255',
                'customer_id' => 'nullable|exists:customers,id',
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'customer_email' => 'nullable|email',
                'event_address' => 'nullable|string',
                'event_date' => 'required|date',
                'event_time' => 'nullable',
                'guest_count' => 'nullable|integer|min:1',
                'special_requests' => 'nullable|string',
                'selected_items' => 'nullable|array',
                'additional_cost' => 'nullable|numeric|min:0',
                'discount' => 'nullable|numeric|min:0'
            ]);

            // إذا تم إرسال اسم الديكور، إنشاء أو إيجاد الديكور
            $decorationId = $request->decoration_id;
            
            if (!$decorationId && $request->decoration_name) {
                // البحث عن ديكور بنفس الاسم أو إنشاء واحد جديد
                $decoration = Decoration::firstOrCreate(
                    ['name' => $request->decoration_name],
                    [
                        'type_name' => 'عادي',
                        'base_price_dinar' => 0,
                        'base_price_dollar' => $request->total_price ?? 0,
                        'currency' => 'dollar',
                        'status' => 'active'
                    ]
                );
                $decorationId = $decoration->id;
            } elseif (!$decorationId) {
                $firstDecoration = Decoration::first();
                $decorationId = $firstDecoration ? $firstDecoration->id : null;
                $decoration = $decorationId ? Decoration::find($decorationId) : null;
            } else {
                $decoration = Decoration::find($decorationId);
            }
            
            // حساب السعر
            if ($decoration) {
                $basePrice = $decoration->currency === 'dollar' ? $decoration->base_price_dollar : $decoration->base_price_dinar;
                $additionalCost = $request->additional_cost ?? 0;
                $discount = $request->discount ?? 0;
                $totalPrice = $basePrice + $additionalCost - $discount;
                $currency = $decoration->currency;
            } else {
                $basePrice = $request->total_price ?? 0;
                $totalPrice = $request->total_price ?? 0;
                $currency = $request->currency ?? 'dollar';
            }

            $orderData = $request->all();
            $orderData['decoration_id'] = $decorationId;
            $orderData['base_price'] = $basePrice;
            $orderData['total_price'] = $totalPrice;
            $orderData['currency'] = $currency;
            $orderData['status'] = 'created';

            // Create customer balance if customer exists and doesn't have one
            if ($request->customer_id) {
                $customer = \App\Models\Customer::find($request->customer_id);
                if ($customer && !$customer->balance) {
                    \App\Models\CustomerBalance::create([
                        'customer_id' => $customer->id,
                        'balance_dollar' => 0,
                        'balance_dinar' => 0,
                        'currency_preference' => 'dinar'
                    ]);
                }
            }

            $order = DecorationOrder::create($orderData);

            // Log the order creation
            \App\Models\Log::create([
                'module_name' => 'Decoration Order',
                'action' => 'Created',
                'badge' => 'success',
                'affected_record_id' => $order->id,
                'original_data' => json_encode([]),
                'updated_data' => json_encode($order->toArray()),
                'by_user_id' => auth()->user()->id,
            ]);

            return redirect()->route('decorations.dashboard')
                ->with('success', __('messages.order_created_successfully'));
                
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Error creating decoration order: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء إنشاء الطلب: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show single order
     */
    public function showOrder(DecorationOrder $order)
    {
        $order->load(['decoration', 'customer', 'assignedEmployee', 'assignedTeam']);
        
        $employees = \App\Models\User::where('is_active', true)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        // Get all payments for this order
        $payments = Box::where('morphed_type', DecorationOrder::class)
            ->where('morphed_id', $order->id)
            ->where('type', 'payment')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Decorations/OrderShow', [
            'translations' => __('messages'),
            'order' => $order,
            'employees' => $employees,
            'payments' => $payments
        ]);
    }

    /**
     * Get employee's assigned orders
     */
    public function myOrders(Request $request)
    {
        $orders = DecorationOrder::with(['decoration', 'customer'])
            ->where('assigned_employee_id', auth()->user()->id)
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->latest()
            ->paginate(15);

        return Inertia::render('Decorations/MyOrders', [
            'translations' => __('messages'),
            'orders' => $orders,
            'filters' => $request->only(['status'])
        ]);
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, DecorationOrder $order)
    {
        $request->validate([
            'status' => 'nullable|in:created,received,executing,partial_payment,full_payment,completed,cancelled',
            'assigned_employee_id' => 'nullable|exists:users,id',
            'assigned_team_id' => 'nullable|exists:decoration_teams,id',
            'total_price' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'scheduled_date' => 'nullable|date',
            'notes' => 'nullable|string'
        ]);

        $originalData = $order->toArray();
        
        // Only include fields that are provided in the request
        $data = [];
        if ($request->has('status') && $request->status) {
            $data['status'] = $request->status;
        }
        if ($request->has('assigned_employee_id')) {
            $data['assigned_employee_id'] = $request->assigned_employee_id;
        }
        if ($request->has('assigned_team_id')) {
            $data['assigned_team_id'] = $request->assigned_team_id;
        }
        if ($request->has('total_price')) {
            $data['total_price'] = $request->total_price;
        }
        if ($request->has('paid_amount')) {
            $data['paid_amount'] = $request->paid_amount;
        }
        if ($request->has('scheduled_date')) {
            $data['scheduled_date'] = $request->scheduled_date;
        }
        if ($request->has('notes')) {
            $data['notes'] = $request->notes;
        }

        // Set timestamp based on status (only if status is being updated)
        if ($request->has('status') && $request->status) {
            switch ($request->status) {
                case 'received':
                    $data['received_at'] = now();
                    break;
                case 'executing':
                    $data['executing_at'] = now();
                    break;
                case 'partial_payment':
                    $data['partial_payment_at'] = now();
                    break;
                case 'full_payment':
                    $data['full_payment_at'] = now();
                    $data['paid_at'] = now();
                    // Set paid amount to total price for full payment
                    $data['paid_amount'] = $order->total_price;
                    break;
                case 'completed':
                    $data['completed_at'] = now();
                    break;
            }
        }

        // Only update if there's data to update
        if (!empty($data)) {
            $order->update($data);
        }

        // If order completed and has assigned employee with commission enabled, accrue commission (only once)
        if (($request->status === 'completed' || $order->fresh()->status === 'completed') && $order->assigned_employee_id) {
            $employee = \App\Models\User::find($order->assigned_employee_id);
            if ($employee && $employee->commission_enabled && $employee->commission_rate_percent > 0) {
                // Check if commission already exists for this order to prevent duplicates
                $existingCommission = EmployeeCommission::where('user_id', $employee->id)
                    ->where('decoration_order_id', $order->id)
                    ->first();

                if (!$existingCommission) {
                    $rate = (float) $employee->commission_rate_percent; // percent
                    $baseAmount = (float) $order->total_price;
                    $commissionAmount = round($baseAmount * ($rate / 100), 2);
                    $currencyCode = $order->currency === 'dollar' ? 'USD' : 'IQD';

                    // Determine period month as first day of completed month
                    $completedAt = $order->completed_at ?: now();
                    $periodMonth = $completedAt->copy()->startOfMonth()->toDateString();

                    EmployeeCommission::create([
                        'user_id' => $employee->id,
                        'decoration_order_id' => $order->id,
                        'commission_rate_percent' => $rate,
                        'base_amount' => $baseAmount,
                        'commission_amount' => $commissionAmount,
                        'currency' => $currencyCode,
                        'status' => 'accrued',
                        'period_month' => $periodMonth,
                        'meta' => [
                            'order_currency' => $order->currency,
                            'auto_generated' => true,
                        ],
                    ]);
                }
            }
        }

        // Create payment record if paid_amount is provided and greater than 0
        if ($request->has('paid_amount') && $request->paid_amount && $request->paid_amount > 0) {
            $this->createPaymentRecord($order, $request->paid_amount, $request->notes);
        }

        // Log the status change
        \App\Models\Log::create([
            'module_name' => 'Decoration Order',
            'action' => 'Status Updated',
            'badge' => 'info',
            'affected_record_id' => $order->id,
            'original_data' => json_encode($originalData),
            'updated_data' => json_encode($order->fresh()->toArray()),
            'by_user_id' => auth()->user()->id,
        ]);

        return back()->with('success', __('messages.status_changed_successfully'));
    }

    /**
     * Create payment record for decoration order
     */
    private function createPaymentRecord(DecorationOrder $order, $amount, $notes = null)
    {
        // Convert currency to 3-letter code
        $currencyCode = $order->currency === 'dollar' ? 'USD' : 'IQD';
        
        // Create payment record in Box model
        Box::create([
            'name' => "دفعة ديكور #{$order->id}",
            'type' => 'payment',
            'amount' => $amount,
            'currency' => $currencyCode,
            'description' => "دفعة لطلب ديكور #{$order->id} - {$order->decoration->name}",
            'details' => json_encode([
                'payment_method' => 'cash',
                'notes' => $notes,
                'order_id' => $order->id,
                'customer_name' => $order->customer_name,
            ]),
            'morphed_type' => DecorationOrder::class,
            'morphed_id' => $order->id,
            'created' => now(),
            'is_active' => true,
            'balance' => 0,
            'balance_usd' => 0,
        ]);

        // Update order paid amount
        $order->increment('paid_amount', $amount);
    }

    /**
     * Update order pricing
     */
    public function updateOrderPricing(Request $request, DecorationOrder $order)
    {
        $request->validate([
            'base_price' => 'required|numeric|min:0',
            'additional_cost' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $originalData = $order->toArray();
        
        $data = $request->only(['base_price', 'additional_cost', 'discount', 'total_price', 'notes']);
        
        // Ensure additional_cost and discount are not null and are valid numbers
        $data['additional_cost'] = floatval($data['additional_cost'] ?? 0);
        $data['discount'] = floatval($data['discount'] ?? 0);
        $data['base_price'] = floatval($data['base_price']);
        $data['total_price'] = floatval($data['total_price']);

        $order->update($data);

        // Log the pricing change
        \App\Models\Log::create([
            'module_name' => 'Decoration Order',
            'action' => 'Pricing Updated',
            'badge' => 'warning',
            'affected_record_id' => $order->id,
            'original_data' => json_encode($originalData),
            'updated_data' => json_encode($order->fresh()->toArray()),
            'by_user_id' => auth()->user()->id,
        ]);

        return back()->with('success', __('messages.pricing_updated_successfully'));
    }

    /**
     * Print decoration order invoice
     */
    public function printOrder(DecorationOrder $order)
    {
        $order->load(['decoration', 'customer', 'assignedEmployee']);
        
        // Get company info from environment variables
        $companyInfo = [
            'name' => env('COMPANY_NAME', 'نظام إدارة الديكورات'),
            'phone' => env('COMPANY_PHONE', ''),
            'email' => env('COMPANY_EMAIL', ''),
            'address' => env('COMPANY_ADDRESS', ''),
            'logo' => env('COMPANY_LOGO', 'dashboard-assets/img/logo.png')
        ];
        
        // Generate QR code URL for verification
        $qrCodeUrl = route('decoration.orders.verify', $order->id);
        
        return view('decorations.print-invoice', compact('order', 'companyInfo', 'qrCodeUrl'));
    }

    /**
     * Verify decoration order invoice
     */
    public function verifyOrder(DecorationOrder $order)
    {
        $order->load(['decoration', 'customer', 'assignedEmployee']);
        
        // Get company info from environment variables
        $companyInfo = [
            'name' => env('COMPANY_NAME', 'نظام إدارة الديكورات'),
            'phone' => env('COMPANY_PHONE', ''),
            'email' => env('COMPANY_EMAIL', ''),
            'address' => env('COMPANY_ADDRESS', ''),
            'logo' => env('COMPANY_LOGO', 'dashboard-assets/img/logo.png')
        ];
        
        return view('decorations.verify-invoice', compact('order', 'companyInfo'));
    }

    /**
     * Print payment receipt
     */
    public function printPaymentReceipt(Box $payment)
    {
        // Load related order
        $order = null;
        if ($payment->morphed_type === DecorationOrder::class && $payment->morphed_id) {
            $order = DecorationOrder::with(['decoration', 'customer'])->find($payment->morphed_id);
        }
        
        // Get company info
        $companyInfo = [
            'name' => env('COMPANY_NAME', 'نظام إدارة الديكورات'),
            'phone' => env('COMPANY_PHONE', ''),
            'email' => env('COMPANY_EMAIL', ''),
            'address' => env('COMPANY_ADDRESS', ''),
            'logo' => env('COMPANY_LOGO', 'dashboard-assets/img/logo.png')
        ];
        
        // Get payment details
        $details = is_string($payment->details) ? json_decode($payment->details, true) : $payment->details;
        
        return view('decorations.payment-receipt', compact('payment', 'order', 'companyInfo', 'details'));
    }

    /**
     * Display decoration payments management
     */
    public function payments(Request $request)
    {
        $activeTab = $request->get('tab', 'payments');
        
        // Get decoration orders with payments
        $orders = DecorationOrder::with(['decoration', 'customer', 'assignedEmployee'])
            ->when($request->search, function ($query, $search) {
                return $query->where('customer_name', 'LIKE', "%{$search}%")
                           ->orWhere('id', 'LIKE', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->currency, function ($query, $currency) {
                return $query->where('currency', $currency);
            })
            ->latest()
            ->paginate(15);

        // Get customer balances
        $customerBalances = CustomerBalance::with('customer')->get();

        // Get payment transactions for decoration orders
        $payments = Box::with('morphed')
            ->where('type', 'payment')
            ->where('morphed_type', DecorationOrder::class)
            ->when($request->search, function ($query, $search) {
                return $query->where('description', 'LIKE', "%{$search}%");
            })
            ->when($request->currency, function ($query, $currency) {
                return $query->where('currency', $currency);
            })
            ->latest()
            ->paginate(15);

        return Inertia::render('Decorations/Payments', [
            'translations' => __('messages'),
            'activeTab' => $activeTab,
            'orders' => $orders,
            'customerBalances' => $customerBalances,
            'payments' => $payments,
        ]);
    }

    /**
     * Add payment to decoration order
     */
    public function addPayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:decoration_orders,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:dollar,dinar',
            'payment_method' => 'required|in:cash,balance,transfer',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $order = DecorationOrder::findOrFail($request->order_id);
            $customer = $order->customer;

            // Check if order is already fully paid to prevent overpayment
            if ($order->paid_amount >= $order->total_price) {
                throw new \Exception('الطلب مدفوع بالكامل بالفعل - لا يمكن إضافة دفعة أخرى');
            }

            // Ensure payment doesn't exceed remaining amount
            $remainingAmount = $order->total_price - $order->paid_amount;
            if ($request->amount > $remainingAmount) {
                throw new \Exception('المبلغ المدخل أكبر من المبلغ المتبقي (' . number_format($remainingAmount, 2) . ')');
            }

            // Convert currency to 3-letter code
            $currencyCode = $request->currency === 'dollar' ? 'USD' : 'IQD';
            
            // Create payment record
            $payment = Box::create([
                'name' => "دفعة ديكور #{$order->id}",
                'amount' => $request->amount,
                'type' => 'payment',
                'description' => "دفعة لطلب ديكور #{$order->id} - {$order->decoration->name}",
                'currency' => $currencyCode,
                'created' => now(),
                'details' => [
                    'notes' => $request->notes,
                    'payment_method' => $request->payment_method,
                    'decoration_order_id' => $order->id,
                    'customer_id' => $customer ? $customer->id : null,
                ],
                'morphed_id' => $order->id,
                'morphed_type' => DecorationOrder::class,
                'is_active' => true,
                'balance' => 0,
                'balance_usd' => 0,
            ]);

            // Update order payment
            $newPaidAmount = $order->paid_amount + $request->amount;
            $order->update([
                'paid_amount' => $newPaidAmount,
                'status' => $newPaidAmount >= $order->total_price ? 'full_payment' : 'partial_payment',
                'paid_at' => now(),
            ]);

            // If payment method is balance, deduct from customer balance
            if ($request->payment_method === 'balance' && $customer) {
                $customerBalance = CustomerBalance::firstOrCreate(
                    ['customer_id' => $customer->id],
                    [
                        'balance_dollar' => 0,
                        'balance_dinar' => 0,
                        'last_transaction_date' => now(),
                    ]
                );

                if ($request->currency === 'dollar') {
                    if ($customerBalance->balance_dollar < $request->amount) {
                        throw new \Exception('الرصيد بالدولار غير كافي');
                    }
                    $customerBalance->balance_dollar -= $request->amount;
                } else {
                    if ($customerBalance->balance_dinar < $request->amount) {
                        throw new \Exception('الرصيد بالدينار غير كافي');
                    }
                    $customerBalance->balance_dinar -= $request->amount;
                }

                $customerBalance->last_transaction_date = now();
                $customerBalance->save();
            }

            DB::commit();

            Log::info('Decoration payment added', [
                'order_id' => $order->id,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'payment_method' => $request->payment_method,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الدفعة بنجاح',
                'payment' => $payment,
                'order' => $order->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Decoration payment failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Add balance to customer
     */
    public function addBalance(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:dollar,dinar',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $customer = Customer::findOrFail($request->customer_id);

            // Convert currency to 3-letter code
            $currencyCode = $request->currency === 'dollar' ? 'USD' : 'IQD';
            
            // Create balance transaction record
            $balanceTransaction = Box::create([
                'name' => "رصيد عميل - {$customer->name}",
                'amount' => $request->amount,
                'type' => 'deposit',
                'description' => "إضافة رصيد للعميل {$customer->name}",
                'currency' => $currencyCode,
                'created' => now(),
                'details' => [
                    'notes' => $request->notes,
                    'customer_id' => $customer->id,
                ],
                'morphed_id' => $customer->id,
                'morphed_type' => Customer::class,
                'is_active' => true,
                'balance' => 0,
                'balance_usd' => 0,
            ]);

            // Update customer balance
            $customerBalance = CustomerBalance::firstOrCreate(
                ['customer_id' => $customer->id],
                [
                    'balance_dollar' => 0,
                    'balance_dinar' => 0,
                    'last_transaction_date' => now(),
                ]
            );

            if ($request->currency === 'dollar') {
                $customerBalance->balance_dollar += $request->amount;
            } else {
                $customerBalance->balance_dinar += $request->amount;
            }

            $customerBalance->last_transaction_date = now();
            $customerBalance->save();

            // إضافة المعاملة في الصندوق الرئيسي (لأن المال يدخل فعلياً)
            // الحصول على mainBox مرة أخرى للتأكد من وجوده
            $userAccount = UserType::where('name', 'account')->first()?->id;
            $mainBoxUser = User::with('wallet')
                ->where('type_id', $userAccount)
                ->where('email', 'mainBox@account.com')
                ->first();
            
            if ($mainBoxUser && $mainBoxUser->wallet) {
                $currency = $request->currency === 'dollar' ? '$' : 'IQD';
                $transaction = $this->accountingController->increaseWallet(
                    (int) round($request->amount),
                    "إضافة رصيد للعميل {$customer->name}",
                    $mainBoxUser->id,
                    $customer->id,
                    Customer::class,
                    0,
                    0,
                    $currency,
                    now()->format('Y-m-d'),
                    0,
                    'in',
                    ['notes' => $request->notes, 'customer_id' => $customer->id, 'type' => 'balance_deposit']
                );
                
                // Log للتحقق من إضافة المعاملة في الصندوق
                Log::info('Balance deposit added to main box', [
                    'customer_id' => $customer->id,
                    'amount' => $request->amount,
                    'currency' => $currency,
                    'main_box_user_id' => $mainBoxUser->id,
                    'wallet_id' => $mainBoxUser->wallet->id,
                    'transaction_id' => $transaction?->id,
                ]);
            } else {
                Log::error('Main box user or wallet not found when adding balance', [
                    'customer_id' => $customer->id,
                    'amount' => $request->amount,
                ]);
            }

            DB::commit();

            Log::info('Customer balance added', [
                'customer_id' => $customer->id,
                'amount' => $request->amount,
                'currency' => $request->currency,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة الرصيد بنجاح',
                'balance' => $customerBalance->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Customer balance addition failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Withdraw balance from customer
     */
    public function withdrawBalance(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:dollar,dinar',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $customer = Customer::findOrFail($request->customer_id);
            $customerBalance = CustomerBalance::where('customer_id', $customer->id)->first();

            if (!$customerBalance) {
                throw new \Exception('العميل لا يملك رصيد');
            }

            // Check if balance is sufficient
            if ($request->currency === 'dollar') {
                if ($customerBalance->balance_dollar < $request->amount) {
                    throw new \Exception('الرصيد بالدولار غير كافي');
                }
                $customerBalance->balance_dollar -= $request->amount;
            } else {
                if ($customerBalance->balance_dinar < $request->amount) {
                    throw new \Exception('الرصيد بالدينار غير كافي');
                }
                $customerBalance->balance_dinar -= $request->amount;
            }

            // Convert currency to 3-letter code
            $currencyCode = $request->currency === 'dollar' ? 'USD' : 'IQD';
            
            // Create withdrawal transaction record
            $withdrawalTransaction = Box::create([
                'name' => "سحب رصيد - {$customer->name}",
                'amount' => $request->amount,
                'type' => 'withdrawal',
                'description' => "سحب رصيد من العميل {$customer->name}",
                'currency' => $currencyCode,
                'created' => now(),
                'details' => [
                    'notes' => $request->notes,
                    'customer_id' => $customer->id,
                ],
                'morphed_id' => $customer->id,
                'morphed_type' => Customer::class,
                'is_active' => true,
                'balance' => 0,
                'balance_usd' => 0,
            ]);

            $customerBalance->last_transaction_date = now();
            $customerBalance->save();

            // إضافة المعاملة في الصندوق الرئيسي (سحب من الصندوق)
            if ($this->mainBox && $this->mainBox->wallet) {
                $currency = $request->currency === 'dollar' ? '$' : 'IQD';
                $this->accountingController->decreaseWallet(
                    (int) round($request->amount),
                    "سحب رصيد من العميل {$customer->name}",
                    $this->mainBox->id,
                    $customer->id,
                    Customer::class,
                    0,
                    0,
                    $currency,
                    now()->format('Y-m-d'),
                    0,
                    'out',
                    ['notes' => $request->notes, 'customer_id' => $customer->id]
                );
            }

            DB::commit();

            Log::info('Customer balance withdrawn', [
                'customer_id' => $customer->id,
                'amount' => $request->amount,
                'currency' => $request->currency,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم سحب الرصيد بنجاح',
                'balance' => $customerBalance->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Customer balance withdrawal failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get customer balance
     */
    public function getCustomerBalance(Request $request)
    {
        $customerId = $request->customer_id;
        
        $balance = CustomerBalance::where('customer_id', $customerId)->first();
        
        if (!$balance) {
            return response()->json([
                'balance_dollar' => 0,
                'balance_dinar' => 0,
            ]);
        }

        return response()->json([
            'balance_dollar' => $balance->balance_dollar,
            'balance_dinar' => $balance->balance_dinar,
        ]);
    }

    /**
     * Display monthly accounting
     */
    public function monthlyAccounting(Request $request)
    {
        $activeTab = $request->get('tab', 'current');
        
        // Get current month and calculate live data for display
        $currentMonth = MonthlyAccount::getCurrentMonth();
        try {
            $currentMonth->calculateMonthlyData();
        } catch (\Throwable $e) {
            \Log::warning('Failed to calculate current month data', ['error' => $e->getMessage()]);
        }
        
        // Get monthly accounts
        $monthlyAccounts = MonthlyAccount::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->paginate(12);

        // Get monthly report if specific month is requested
        $monthlyReport = null;
        if ($request->year && $request->month) {
            $monthlyReport = MonthlyAccount::getMonthlyReport($request->year, $request->month);
        }

        return Inertia::render('Decorations/MonthlyAccounting', [
            'translations' => __('messages'),
            'activeTab' => $activeTab,
            'currentMonth' => $currentMonth,
            'monthlyAccounts' => $monthlyAccounts,
            'monthlyReport' => $monthlyReport,
        ]);
    }

    /**
     * Close current month
     */
    public function closeMonth(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();

        try {
            $currentMonth = MonthlyAccount::getCurrentMonth();
            
            if ($currentMonth->status === 'closed') {
                throw new \Exception('الشهر مغلق بالفعل');
            }

            // Update notes if provided
            if ($request->notes) {
                $currentMonth->notes = $request->notes;
                $currentMonth->save();
            }

            // Close the month
            $currentMonth->closeMonth(auth()->id());

            DB::commit();

            Log::info('Month closed', [
                'year' => $currentMonth->year,
                'month' => $currentMonth->month,
                'closed_by' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إغلاق الشهر بنجاح',
                'monthly_account' => $currentMonth->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Month closing failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get monthly report
     */
    public function getMonthlyReport(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $monthlyReport = MonthlyAccount::getMonthlyReport($request->year, $request->month);

        if (!$monthlyReport) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد بيانات لهذا الشهر',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $monthlyReport,
        ]);
    }

    /**
     * Update monthly account
     */
    public function updateMonthlyAccount(Request $request, MonthlyAccount $monthlyAccount)
    {
        $request->validate([
            'opening_balance_dollar' => 'nullable|numeric|min:0',
            'opening_balance_dinar' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $monthlyAccount->update($request->only([
            'opening_balance_dollar',
            'opening_balance_dinar',
            'notes',
        ]));

        Log::info('Monthly account updated', [
            'monthly_account_id' => $monthlyAccount->id,
            'year' => $monthlyAccount->year,
            'month' => $monthlyAccount->month,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الحساب الشهري بنجاح',
            'monthly_account' => $monthlyAccount->fresh(),
        ]);
    }

    /**
     * Recalculate monthly data
     */
    public function recalculateMonthlyData(MonthlyAccount $monthlyAccount)
    {
        try {
            $monthlyAccount->calculateMonthlyData();
            $monthlyAccount->save();

            Log::info('Monthly data recalculated', [
                'monthly_account_id' => $monthlyAccount->id,
                'year' => $monthlyAccount->year,
                'month' => $monthlyAccount->month,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة حساب البيانات بنجاح',
                'monthly_account' => $monthlyAccount->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Monthly data recalculation failed', [
                'error' => $e->getMessage(),
                'monthly_account_id' => $monthlyAccount->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Payout accrued employee commissions for a given month and mark them as paid.
     */
    public function payoutMonthlyCommissions(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year = (int) $request->year;
        $month = (int) $request->month;
        $periodDate = now()->setYear($year)->setMonth($month)->startOfMonth();

        DB::beginTransaction();
        try {
            $commissions = EmployeeCommission::whereDate('period_month', $periodDate->toDateString())
                ->where('status', 'accrued')
                ->get();

            foreach ($commissions as $commission) {
                $commission->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'paid_by' => auth()->id(),
                ]);
            }

            // Recalculate monthly data to reflect zero accrued commissions
            $monthlyAccount = MonthlyAccount::where('year', $year)->where('month', $month)->first();
            if ($monthlyAccount) {
                $monthlyAccount->calculateMonthlyData()->save();
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'تم دفع العمولات وخصمها من الحسابات للشهر المحدد',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Payout commissions failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
