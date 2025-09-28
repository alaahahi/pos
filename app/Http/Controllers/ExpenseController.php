<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\User;
use App\Models\Box;
use App\Models\SystemConfig;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:read expenses');
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
        
        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'تم حذف المصروف بنجاح');
    }

    /**
     * Update box balance based on expense
     */
    private function updateBoxBalance(Expense $expense, string $action)
    {
        $box = Box::where('is_active', true)->first();
        if (!$box) {
            return;
        }

        // Update box balance based on currency
        if ($expense->currency === 'USD') {
            if ($action === 'create') {
                $box->decrement('balance_usd', $expense->amount);
                // Create transaction record
                $this->createTransaction($expense, 'out', $box);
            } elseif ($action === 'delete') {
                $box->increment('balance_usd', $expense->amount);
                // Create transaction record
                $this->createTransaction($expense, 'in', $box);
            }
        } else { // IQD
            if ($action === 'create') {
                $box->decrement('balance', $expense->amount);
                // Create transaction record
                $this->createTransaction($expense, 'out', $box);
            } elseif ($action === 'delete') {
                $box->increment('balance', $expense->amount);
                // Create transaction record
                $this->createTransaction($expense, 'in', $box);
            }
        }
    }

    /**
     * Create transaction record for expense
     */
    private function createTransaction(Expense $expense, string $type, Box $box)
    {
        Transactions::create([
            'amount' => $expense->amount,
            'type' => $type,
            'description' => "مصروف: {$expense->title}",
            'currency' => $expense->currency,
            'created' => $expense->expense_date,
            'morphed_id' => $box->id,
            'morphed_type' => 'App\Models\Box',
        ]);
    }
}