<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Order;
use App\Models\Log;
use Spatie\Permission\Models\Role;

use App\Models\Product;
use App\Models\Customer;
use App\Models\DecorationOrder;
use App\Models\UserType;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
class DashboardController extends Controller
{

    public function index()
{
    // Fetch all roles and prepare chart data
    $UserPerRolechartData = $this->getRolesChartData();

    // Group logs and prepare chart data
    $actionsChartData = $this->getLogsChartDataByAction();
    $modulesChartData = $this->getLogsChartDataByModule();
    $usersChartData = $this->getLogsChartDataByUser();
    $statusChartData = $this->getUsersChartDataByStatus();
    
    // Decoration orders statistics
    $decorationOrdersChartData = $this->getDecorationOrdersChartData();
    
    // Get box balance
    $boxBalance = $this->getBoxBalance();

    return Inertia::render('Dashboard', [
        'translations' => __('messages'),
        
        // Basic counts
        'productCount' => Product::count(),
        'activeProductCount' => Product::where('is_active', 1)->count(),
        'lowStockCount' => Product::where('quantity', '<=', 5)->count(),
        'customerCount' => Customer::count(),
        'orderCount' => Order::count(),
        'orderDueCount' => Order::where('status', 'due')->count(),
        'orderCompletCount' => Order::where('status', 'paid')->count(),
        'boxBalanceUSD' => $boxBalance['usd'],
        'boxBalanceIQD' => $boxBalance['iqd'],
        
        // Decoration orders
        'decorationOrderCount' => DecorationOrder::count(),
        'decorationOrderPendingCount' => DecorationOrder::where('status', 'created')->count(),
        'decorationOrderCompletedCount' => DecorationOrder::where('status', 'completed')->count(),
        'decorationOrderPaidCount' => DecorationOrder::where('status', 'full_payment')->count(),
        
        // Chart data
        'ordersStatusChartData' => $this->getOrdersStatusChartData(),
        'productsStatusChartData' => $this->getProductsStatusChartData(),
        'decorationOrdersChartData' => $decorationOrdersChartData,
        'monthlySalesChartData' => $this->getMonthlySalesChartData(),
    ]);
}


    private function getRolesChartData()
    {
        return Cache::remember('roles_chart_data', 60, function () {
            $roles = Role::withCount('users')->get();
            return [
                'labels' => $roles->pluck('name'),
                'datasets' => [
                    [
                        'label' => 'Users per Role',
                        'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56'],
                        'data' => $roles->pluck('users_count'),
                    ],
                ],
            ];
        });
    }
    
    private function getLogsChartDataByAction()
    {
        return Cache::remember('logs_by_action_chart_data', 60, function () {
            $logsByAction = Log::select('action', \DB::raw('count(*) as total'))
                ->groupBy('action')
                ->get();
            
            return [
                'labels' => $logsByAction->pluck('action'),
                'datasets' => [
                    [
                        'label' => 'Logs by Action',
                        'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56'],
                        'data' => $logsByAction->pluck('total'),
                    ],
                ],
            ];
        });
    }
    
    private function getLogsChartDataByModule()
    {
        return Cache::remember('logs_by_module_chart_data', 60, function () {
            $logsByModule = Log::select('module_name', \DB::raw('count(*) as total'))
                ->groupBy('module_name')
                ->get();
    
            return [
                'labels' => $logsByModule->pluck('module_name'),
                'datasets' => [
                    [
                        'label' => 'Logs by Module',
                        'backgroundColor' => ['#4BC0C0', '#FF9F40', '#9966FF'],
                        'data' => $logsByModule->pluck('total'),
                    ],
                ],
            ];
        });
    }
    
    private function getLogsChartDataByUser()
    {
        return Cache::remember('logs_by_user_chart_data', 60, function () {
            $logsByUser = Log::select('by_user_id', \DB::raw('count(*) as total'))
                ->with('user:id,name')
                ->groupBy('by_user_id')
                ->get();
    
            return [
                'labels' => $logsByUser->pluck('user.name'),
                'datasets' => [
                    [
                        'label' => 'Logs by User',
                        'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'],
                        'data' => $logsByUser->pluck('total'),
                    ],
                ],
            ];
        });
    }
    
    private function getOrdersStatusChartData()
    {
        return Cache::remember('orders_status_chart_data', 60, function () {
            $ordersByStatus = Order::select('status', \DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();
    
            return [
                'labels' => ['مدفوعة', 'معلقة', 'مكتملة'],
                'datasets' => [
                    [
                        'label' => 'حالة الفواتير',
                        'backgroundColor' => ['#28a745', '#ffc107', '#17a2b8'],
                        'data' => [
                            $ordersByStatus->where('status', 'paid')->first()->total ?? 0,
                            $ordersByStatus->where('status', 'due')->first()->total ?? 0,
                            $ordersByStatus->where('status', 'completed')->first()->total ?? 0,
                        ],
                    ],
                ],
            ];
        });
    }

    private function getProductsStatusChartData()
    {
        return Cache::remember('products_status_chart_data', 60, function () {
            $activeProducts = Product::where('is_active', 1)->count();
            $inactiveProducts = Product::where('is_active', 0)->count();
            $lowStockProducts = Product::where('quantity', '<=', 5)->count();
    
            return [
                'labels' => ['نشطة', 'غير نشطة', 'منخفضة المخزون'],
                'datasets' => [
                    [
                        'label' => 'حالة المنتجات',
                        'backgroundColor' => ['#28a745', '#6c757d', '#dc3545'],
                        'data' => [$activeProducts, $inactiveProducts, $lowStockProducts],
                    ],
                ],
            ];
        });
    }

    private function getMonthlySalesChartData()
    {
        return Cache::remember('monthly_sales_chart_data', 60, function () {
            // استخدام دالة مناسبة حسب نوع قاعدة البيانات
            $connection = config('database.default');
            $isSQLite = $connection === 'sync_sqlite' || $connection === 'sqlite';
            
            if ($isSQLite) {
                // SQLite: استخدام strftime
                $monthlySales = Order::selectRaw("CAST(strftime('%m', created_at) AS INTEGER) as month, SUM(total_amount) as total")
                    ->whereRaw("strftime('%Y', created_at) = ?", [now()->year])
                    ->groupBy('month')
                    ->get();
            } else {
                // MySQL: استخدام MONTH
                $monthlySales = Order::selectRaw('MONTH(created_at) as month, SUM(total_amount) as total')
                    ->whereYear('created_at', now()->year)
                    ->groupBy('month')
                    ->get();
            }
    
            $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
                     'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
            
            $data = array_fill(0, 12, 0);
            foreach ($monthlySales as $sale) {
                $data[$sale->month - 1] = $sale->total;
            }
    
            return [
                'labels' => $months,
                'datasets' => [
                    [
                        'label' => 'مبيعات الشهرية (IQD)',
                        'backgroundColor' => '#007bff',
                        'data' => $data,
                    ],
                ],
            ];
        });
    }

    private function getUsersChartDataByStatus()
    {
        return Cache::remember('users_by_status_chart_data', 60, function () {
            $usersByStatus = User::select('is_active', \DB::raw('count(*) as total'))
                ->groupBy('is_active')
                ->get();
    
            return [
                'labels' => ['Inactive', 'Active'],
                'datasets' => [
                    [
                        'label' => 'Users by Status',
                        'backgroundColor' => ['#FF6384', '#36A2EB'],
                        'data' => [
                            $usersByStatus->where('is_active', 0)->first()->total ?? 0,
                            $usersByStatus->where('is_active', 1)->first()->total ?? 0,
                        ],
                    ],
                ],
            ];
        });
    }
    
    private function getDecorationOrdersChartData()
    {
        return Cache::remember('decoration_orders_by_status_chart_data', 60, function () {
            $ordersByStatus = DecorationOrder::select('status', \DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();
    
            $statusLabels = [
                'created' => 'تم الإنشاء',
                'received' => 'تم الاستلام',
                'executing' => 'قيد التنفيذ',
                'partial_payment' => 'دفع جزئي',
                'full_payment' => 'دفع كامل',
                'completed' => 'مكتمل',
                'cancelled' => 'ملغي'
            ];
    
            return [
                'labels' => $ordersByStatus->map(function ($item) use ($statusLabels) {
                    return $statusLabels[$item->status] ?? $item->status;
                }),
                'datasets' => [
                    [
                        'label' => 'طلبات الديكور حسب الحالة',
                        'backgroundColor' => ['#6c757d', '#17a2b8', '#007bff', '#ffc107', '#28a745', '#28a745', '#dc3545'],
                        'data' => $ordersByStatus->pluck('total'),
                    ],
                ],
            ];
        });
    }
    
    private function getBoxBalance()
    {
        try {
            // Get main box user
            $userAccount = UserType::where('name', 'account')->first();
            
            if (!$userAccount) {
                return ['usd' => 0, 'iqd' => 0];
            }
            
            $mainBoxUser = User::with('wallet')
                ->where('type_id', $userAccount->id)
                ->where('email', 'mainBox@account.com')
                ->first();
            
            if (!$mainBoxUser || !$mainBoxUser->wallet) {
                return ['usd' => 0, 'iqd' => 0];
            }
            
            return [
                'usd' => round($mainBoxUser->wallet->balance ?? 0, 2),
                'iqd' => round($mainBoxUser->wallet->balance_dinar ?? 0, 2),
            ];
        } catch (\Exception $e) {
            return ['usd' => 0, 'iqd' => 0];
        }
    }
    
}    
