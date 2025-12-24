<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PurchaseInvoice;
use App\Models\Expense;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:read reports', ['only' => ['index', 'sales', 'purchases', 'expenses', 'summary']]);
    }

    /**
     * صفحة التقارير الرئيسية
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'sales');
        
        return Inertia::render('Reports/Index', [
            'activeTab' => $activeTab,
            'translations' => __('messages'),
        ]);
    }

    /**
     * تقرير المبيعات
     */
    public function sales(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $customerId = $request->get('customer_id');
        $productId = $request->get('product_id');
        $format = $request->get('format', 'html'); // html, pdf, print

        $query = Order::with(['customer', 'products'])
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('date');

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($productId) {
            $query->whereHas('products', function($q) use ($productId) {
                $q->where('products.id', $productId);
            });
        }

        $orders = $query->orderBy('date', 'desc')->get();

        // حساب الإحصائيات
        $totalAmount = $orders->sum('final_amount') ?? $orders->sum('total_amount');
        $totalPaid = $orders->sum('total_paid');
        $totalDiscount = $orders->sum('discount_amount');
        $totalCount = $orders->count();
        $averageOrder = $totalCount > 0 ? $totalAmount / $totalCount : 0;

        // تجميع حسب التاريخ
        $groupedByDate = $orders->groupBy(function($order) {
            return Carbon::parse($order->date)->format('Y-m-d');
        })->map(function($group) {
            return [
                'date' => Carbon::parse($group->first()->date)->format('Y-m-d'),
                'count' => $group->count(),
                'total' => $group->sum('final_amount') ?? $group->sum('total_amount'),
                'paid' => $group->sum('total_paid'),
            ];
        });

        // تجميع حسب العميل
        $groupedByCustomer = $orders->groupBy('customer_id')->map(function($group) {
            $customer = $group->first()->customer;
            return [
                'customer_id' => $group->first()->customer_id,
                'customer_name' => $customer->name ?? 'N/A',
                'count' => $group->count(),
                'total' => $group->sum('final_amount') ?? $group->sum('total_amount'),
                'paid' => $group->sum('total_paid'),
            ];
        })->sortByDesc('total')->values();

        // تجميع حسب المنتج
        $productsData = [];
        foreach ($orders as $order) {
            foreach ($order->products as $product) {
                $quantity = $product->pivot->quantity ?? 1;
                $price = $product->pivot->price ?? 0;
                $total = $quantity * $price;
                
                if (!isset($productsData[$product->id])) {
                    $productsData[$product->id] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => 0,
                        'total' => 0,
                    ];
                }
                $productsData[$product->id]['quantity'] += $quantity;
                $productsData[$product->id]['total'] += $total;
            }
        }
        $groupedByProduct = collect($productsData)->sortByDesc('total')->values();

        $data = [
            'orders' => $orders,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'customer_id' => $customerId,
                'product_id' => $productId,
            ],
            'statistics' => [
                'total_amount' => $totalAmount,
                'total_paid' => $totalPaid,
                'total_discount' => $totalDiscount,
                'total_count' => $totalCount,
                'average_order' => $averageOrder,
                'remaining' => $totalAmount - $totalPaid,
            ],
            'grouped_by_date' => $groupedByDate->values(),
            'grouped_by_customer' => $groupedByCustomer,
            'grouped_by_product' => $groupedByProduct,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];

        if ($format === 'print') {
            return view('reports.sales-report', $data);
        }

        return response()->json($data);
    }

    /**
     * تقرير المشتريات
     */
    public function purchases(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $supplierId = $request->get('supplier_id');
        $format = $request->get('format', 'html');

        $query = PurchaseInvoice::with(['supplier', 'items.product'])
            ->whereBetween('invoice_date', [$startDate, $endDate]);

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $purchases = $query->orderBy('invoice_date', 'desc')->get();

        // حساب الإحصائيات
        $totalAmount = $purchases->sum('total_amount');
        $totalCount = $purchases->count();
        $averagePurchase = $totalCount > 0 ? $totalAmount / $totalCount : 0;

        // تجميع حسب المورد
        $groupedBySupplier = $purchases->groupBy('supplier_id')->map(function($group) {
            $supplier = $group->first()->supplier;
            return [
                'supplier_id' => $group->first()->supplier_id,
                'supplier_name' => $supplier->name ?? 'N/A',
                'count' => $group->count(),
                'total' => $group->sum('total_amount'),
            ];
        })->sortByDesc('total')->values();

        // تجميع حسب المنتج
        $productsData = [];
        foreach ($purchases as $purchase) {
            foreach ($purchase->items as $item) {
                $product = $item->product;
                if ($product) {
                    if (!isset($productsData[$product->id])) {
                        $productsData[$product->id] = [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'quantity' => 0,
                            'total' => 0,
                        ];
                    }
                    $productsData[$product->id]['quantity'] += $item->quantity ?? 1;
                    $productsData[$product->id]['total'] += ($item->quantity ?? 1) * ($item->price ?? 0);
                }
            }
        }
        $groupedByProduct = collect($productsData)->sortByDesc('total')->values();

        $data = [
            'purchases' => $purchases,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'supplier_id' => $supplierId,
            ],
            'statistics' => [
                'total_amount' => $totalAmount,
                'total_count' => $totalCount,
                'average_purchase' => $averagePurchase,
            ],
            'grouped_by_supplier' => $groupedBySupplier,
            'grouped_by_product' => $groupedByProduct,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];

        if ($format === 'print') {
            return view('reports.purchases-report', $data);
        }

        return response()->json($data);
    }

    /**
     * تقرير المصاريف
     */
    public function expenses(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $category = $request->get('category');
        $currency = $request->get('currency');
        $format = $request->get('format', 'html');

        $query = Expense::with('creator')
            ->whereBetween('expense_date', [$startDate, $endDate]);

        if ($category) {
            $query->where('category', $category);
        }

        if ($currency) {
            $query->where('currency', $currency);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();

        // حساب الإحصائيات
        $totalAmount = $expenses->sum('amount');
        $totalCount = $expenses->count();
        $averageExpense = $totalCount > 0 ? $totalAmount / $totalCount : 0;

        // تجميع حسب الفئة
        $groupedByCategory = $expenses->groupBy('category')->map(function($group, $category) {
            return [
                'category' => $category ?: 'غير محدد',
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ];
        })->sortByDesc('total')->values();

        // تجميع حسب العملة
        $groupedByCurrency = $expenses->groupBy('currency')->map(function($group, $currency) {
            return [
                'currency' => $currency ?: 'N/A',
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ];
        })->sortByDesc('total')->values();

        $data = [
            'expenses' => $expenses,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'category' => $category,
                'currency' => $currency,
            ],
            'statistics' => [
                'total_amount' => $totalAmount,
                'total_count' => $totalCount,
                'average_expense' => $averageExpense,
            ],
            'grouped_by_category' => $groupedByCategory,
            'grouped_by_currency' => $groupedByCurrency,
            'categories' => Expense::distinct()->pluck('category')->filter()->values(),
            'currencies' => ['IQD', 'USD'],
        ];

        if ($format === 'print') {
            return view('reports.expenses-report', $data);
        }

        return response()->json($data);
    }

    /**
     * تقرير شامل (جميع التقارير معاً)
     */
    public function summary(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::now()->format('Y-m-d'));
        $format = $request->get('format', 'html');

        // المبيعات
        $salesOrders = Order::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('date')
            ->get();
        $salesTotal = $salesOrders->sum(function($order) {
            return $order->final_amount ?? $order->total_amount ?? 0;
        });
        $salesCount = Order::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('date')
            ->count();

        // المشتريات
        $purchasesTotal = PurchaseInvoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->sum('total_amount');
        $purchasesCount = PurchaseInvoice::whereBetween('invoice_date', [$startDate, $endDate])
            ->count();

        // المصاريف
        $expensesTotal = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');
        $expensesCount = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->count();

        // الربح الإجمالي
        $profit = $salesTotal - $purchasesTotal - $expensesTotal;

        $data = [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'sales' => [
                'total' => $salesTotal,
                'count' => $salesCount,
            ],
            'purchases' => [
                'total' => $purchasesTotal,
                'count' => $purchasesCount,
            ],
            'expenses' => [
                'total' => $expensesTotal,
                'count' => $expensesCount,
            ],
            'profit' => $profit,
            'profit_percentage' => $salesTotal > 0 ? ($profit / $salesTotal) * 100 : 0,
        ];

        if ($format === 'print') {
            return view('reports.summary-report', $data);
        }

        return response()->json($data);
    }
}

