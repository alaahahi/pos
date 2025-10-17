<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\ProductPriceHistory;
use App\Models\CashboxTransaction;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PurchaseInvoice::with(['supplier', 'creator', 'items.product'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                      $supplierQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('invoice_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('invoice_date', '<=', $request->date_to);
        }

        $purchaseInvoices = $query->paginate(15);

        // Get suppliers for filter dropdown
        $suppliers = Supplier::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('PurchaseInvoices/Index', [
            'purchaseInvoices' => $purchaseInvoices,
            'suppliers' => $suppliers,
            'filters' => $request->only(['search', 'supplier_id', 'date_from', 'date_to']),
            'translations' => __('messages'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::where('is_active', true)
            ->select('id', 'name', 'phone')
            ->orderBy('name')
            ->get();

        $products = Product::select('id', 'name', 'barcode', 'price', 'quantity', 'image')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('PurchaseInvoices/Create', [
            'suppliers' => $suppliers,
            'products' => $products,
            'translations' => __('messages'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.sales_price' => 'required|numeric|min:0',
            'withdraw_from_cashbox' => 'boolean',
            'currency' => 'required|in:IQD,$',
        ]);

        DB::beginTransaction();

        try {
            // Create purchase invoice
            $purchaseInvoice = PurchaseInvoice::create([
                'supplier_id' => $request->supplier_id,
                'total_amount' => 0, // Will be calculated
                'invoice_date' => $request->invoice_date,
                'notes' => $request->notes,
                'invoice_number' => PurchaseInvoice::generateInvoiceNumber(),
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;

            // Create invoice items and update products
            foreach ($request->items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                // Store old price for history
                $oldPrice = $product->price;
                
                // Create invoice item
                $invoiceItem = PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $purchaseInvoice->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'cost_price' => $item['cost_price'],
                    'sales_price' => $item['sales_price'],
                    'total' => $item['quantity'] * $item['cost_price'],
                ]);

                $totalAmount += $invoiceItem->total;

                // Update product quantity and price
                $product->update([
                    'quantity' => $product->quantity + $item['quantity'],
                    'price' => $item['sales_price'],
                ]);

                // Create price history if price changed
                if ($oldPrice != $item['sales_price']) {
                    ProductPriceHistory::create([
                        'product_id' => $product->id,
                        'old_price' => $oldPrice,
                        'new_price' => $item['sales_price'],
                        'change_reason' => 'purchase',
                        'changed_by' => auth()->id(),
                        'purchase_invoice_id' => $purchaseInvoice->id,
                    ]);
                }
            }

            // Update invoice total
            $purchaseInvoice->update(['total_amount' => $totalAmount]);

            // Handle cashbox withdrawal if requested
            if ($request->withdraw_from_cashbox) {
                $this->createCashboxTransaction($purchaseInvoice, $totalAmount, $request->currency);
            }

            DB::commit();

            return redirect()->route('purchase-invoices.index')
                ->with('success', 'تم إنشاء فاتورة المشتريات بنجاح');

        } catch (\Exception $e) {
            DB::rollback();
            
            return back()->withErrors([
                'error' => 'حدث خطأ أثناء إنشاء فاتورة المشتريات: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Search products by name or barcode
     */
    public function searchProducts(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $products = Product::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('barcode', 'like', "%{$query}%");
            })
            ->select('id', 'name', 'barcode', 'price', 'quantity', 'image')
            ->limit(10)
            ->get();

        return response()->json($products);
    }

    /**
     * Search suppliers by name
     */
    public function searchSuppliers(Request $request)
    {
        $query = $request->get('q', '');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $suppliers = Supplier::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->select('id', 'name', 'phone')
            ->limit(10)
            ->get();

        return response()->json($suppliers);
    }

    /**
     * Create cashbox transaction for withdrawal
     */
    private function createCashboxTransaction(PurchaseInvoice $purchaseInvoice, $amount, $currency)
    {
        // Get main box
        $userAccount = UserType::where('name', 'account')->first();
        $mainBox = User::with('wallet')
            ->where('type_id', $userAccount->id)
            ->where('email', 'mainBox@account.com')
            ->first();

        if (!$mainBox || !$mainBox->wallet) {
            throw new \Exception('الصندوق الرئيسي غير موجود');
        }

        // Create withdrawal transaction
        $accountingController = new AccountingController();
        $transaction = $accountingController->decreaseWallet(
            $amount,
            "شراء منتجات - فاتورة رقم: {$purchaseInvoice->invoice_number}",
            $mainBox->id,
            $mainBox->id,
            'App\Models\User',
            0,
            0,
            $currency,
            $purchaseInvoice->invoice_date
        );

        // Create cashbox transaction record
        CashboxTransaction::create([
            'type' => 'withdrawal',
            'amount' => $amount,
            'currency' => $currency,
            'description' => "شراء منتجات - فاتورة رقم: {$purchaseInvoice->invoice_number}",
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $purchaseInvoice->id,
            'user_id' => auth()->id(),
            'from_wallet_id' => $mainBox->wallet->id,
            'to_wallet_id' => null,
            'transaction_date' => $purchaseInvoice->invoice_date,
        ]);

        return $transaction;
    }
}
