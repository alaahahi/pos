<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\User;
use App\Models\UserType;
use App\Models\Box;
use App\Models\SystemConfig;
use App\Models\Transactions;
use App\Http\Controllers\AccountingController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    protected $accountingController;
    protected $mainBox;
    protected $userAccount;

    public function __construct(AccountingController $accountingController)
    {
        $this->middleware('can:read expenses');
        $this->accountingController = $accountingController;
        $this->userAccount = UserType::where('name', 'account')->first()?->id;
        $this->mainBox = User::with('wallet')
            ->where('type_id', $this->userAccount)
            ->where('email', 'mainBox@account.com')
            ->first();
    }

    /**
     * Display a listing of expenses
     */
    public function index(Request $request)
    {
        $filters = [
            'category' => $request->category,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'search' => $request->search,
        ];

        $expensesQuery = Expense::with('creator')->latest('expense_date');

        // Apply filters
        if ($filters['category']) {
            $expensesQuery->where('category', $filters['category']);
        }

        if ($filters['start_date']) {
            $expensesQuery->where('expense_date', '>=', $filters['start_date']);
        }

        if ($filters['end_date']) {
            $expensesQuery->where('expense_date', '<=', $filters['end_date']);
        }

        if ($filters['search']) {
            $expensesQuery->where(function ($query) use ($filters) {
                $query->where('title', 'LIKE', "%{$filters['search']}%")
                      ->orWhere('description', 'LIKE', "%{$filters['search']}%");
            });
        }

        $expenses = $expensesQuery->paginate(15);

        // Get statistics
        $totalExpenses = Expense::sum('amount');
        $monthlyExpenses = Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');
        $todayExpenses = Expense::whereDate('expense_date', today())->sum('amount');

        // Get expenses by category
        $expensesByCategory = Expense::select('category', DB::raw('SUM(amount) as total'))
            ->groupBy('category')
            ->get();

        return Inertia::render('Expenses/Index', [
            'translations' => __('messages'),
            'expenses' => $expenses,
            'filters' => $filters,
            'categories' => Expense::getCategories(),
            'currencies' => Expense::getCurrencies(),
            'statistics' => [
                'total' => $totalExpenses,
                'monthly' => $monthlyExpenses,
                'today' => $todayExpenses,
                'by_category' => $expensesByCategory,
            ],
        ]);
    }

    /**
     * Show the form for creating a new expense
     */
    public function create()
    {
        return Inertia::render('Expenses/Create', [
            'translations' => __('messages'),
            'categories' => Expense::getCategories(),
            'currencies' => Expense::getCurrencies(),
        ]);
    }

    /**
     * Store a newly created expense
     */
    public function store(Request $request)
    {
        $this->authorize('create expenses');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:IQD,USD',
            'category' => 'required|string',
            'expense_date' => 'required|date',
        ]);

        $expense = Expense::create([
            ...$validated,
            'created_by' => Auth::id(),
        ]);
        
        // إضافة job للمزامنة من السيرفر إلى المحلي
        try {
            \App\Jobs\SyncFromServerJob::dispatch('expenses', $expense->id, 'insert')
                ->onQueue('sync-from-server');
            \Illuminate\Support\Facades\Log::info('Dispatched sync from server job for expense', [
                'expense_id' => $expense->id,
                'app_env' => config('app.env'),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to dispatch sync from server job for expense', [
                'expense_id' => $expense->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Update box balance
        $this->updateBoxBalance($expense, 'create');

        return redirect()->route('expenses.index')
            ->with('success', 'تم إضافة المصروف بنجاح');
    }

    /**
     * Show the form for editing the specified expense
     */
    public function edit(Expense $expense)
    {
        return Inertia::render('Expenses/Edit', [
            'translations' => __('messages'),
            'expense' => $expense,
            'categories' => Expense::getCategories(),
            'currencies' => Expense::getCurrencies(),
        ]);
    }

    /**
     * Update the specified expense
     */
    public function update(Request $request, Expense $expense)
    {
        $this->authorize('update expenses');

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|in:IQD,USD',
            'category' => 'required|string',
            'expense_date' => 'required|date',
        ]);

        $oldExpense = $expense->replicate();
        $expense->update($validated);
        
        // إضافة job للمزامنة من السيرفر إلى المحلي
        try {
            \App\Jobs\SyncFromServerJob::dispatch('expenses', $expense->id, 'update')
                ->onQueue('sync-from-server');
            \Illuminate\Support\Facades\Log::info('Dispatched sync from server job for expense update', [
                'expense_id' => $expense->id,
                'app_env' => config('app.env'),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to dispatch sync from server job for expense update', [
                'expense_id' => $expense->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Update box balance
        $this->updateBoxBalance($oldExpense, 'delete'); // Remove old amount
        $this->updateBoxBalance($expense, 'create'); // Add new amount

        return redirect()->route('expenses.index')
            ->with('success', 'تم تحديث المصروف بنجاح');
    }

    /**
     * Remove the specified expense
     */
    public function destroy(Expense $expense)
    {
        $this->authorize('delete expenses');

        // Update box balance before deleting
        $this->updateBoxBalance($expense, 'delete');
        
        // إضافة job للمزامنة من السيرفر إلى المحلي (للحذف)
        try {
            \App\Jobs\SyncFromServerJob::dispatch('expenses', $expense->id, 'delete')
                ->onQueue('sync-from-server');
            \Illuminate\Support\Facades\Log::info('Dispatched sync from server job for expense delete', [
                'expense_id' => $expense->id,
                'app_env' => config('app.env'),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to dispatch sync from server job for expense delete', [
                'expense_id' => $expense->id,
                'error' => $e->getMessage(),
            ]);
        }
        
        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'تم حذف المصروف بنجاح');
    }

    /**
     * Update box balance based on expense using wallet system
     */
    private function updateBoxBalance(Expense $expense, string $action)
    {
        if (!$this->mainBox || !$this->mainBox->wallet) {
            return;
        }

        $currency = $expense->currency === 'USD' ? '$' : 'IQD';
        $description = "مصروف: {$expense->title}" . ($expense->description ? " - {$expense->description}" : '');
        $expenseDate = is_numeric($expense->expense_date) ? $expense->expense_date : strtotime($expense->expense_date);

        if ($action === 'create') {
            // Decrease wallet balance (expense is outgoing)
            $this->accountingController->decreaseWallet(
                (int) round($expense->amount),
                $description,
                $this->mainBox->id,
                $expense->id,
                'App\Models\Expense',
                0,
                0,
                $currency,
                $expenseDate
            );
        } elseif ($action === 'delete') {
            // Increase wallet balance (revert expense)
            $this->accountingController->increaseWallet(
                (int) round($expense->amount),
                "إلغاء {$description}",
                $this->mainBox->id,
                $expense->id,
                'App\Models\Expense',
                0,
                0,
                $currency,
                $expenseDate
            );
        }
    }
}