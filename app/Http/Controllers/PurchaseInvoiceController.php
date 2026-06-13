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
use App\Models\Vehicle;
use App\Services\LogService;
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

        $products = Product::select('id', 'name', 'barcode', 'price', 'price_cost', 'quantity', 'image')
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
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required_without:items.*.product_id|nullable|string|max:255',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.cost_price' => 'nullable|numeric|min:0',
            'items.*.sales_price' => 'nullable|numeric|min:0',
            'items.*.vehicles' => 'nullable|array',
            'items.*.vehicles.*.vin' => 'required_with:items.*.vehicles|string|size:17|distinct',
            'items.*.vehicles.*.color' => 'nullable|string|max:100',
            'items.*.vehicles.*.vehicle_model' => 'nullable|string|max:100',
            'items.*.vehicles.*.make' => 'nullable|string|max:100',
            'items.*.vehicles.*.year' => 'nullable|string|max:4',
            'withdraw_from_cashbox' => 'boolean',
            'currency' => 'required|in:IQD,$',
        ]);

        DB::beginTransaction();

        try {
            $purchaseInvoice = PurchaseInvoice::create([
                'supplier_id' => $request->supplier_id,
                'total_amount' => 0,
                'invoice_date' => $request->invoice_date,
                'notes' => $request->notes,
                'invoice_number' => PurchaseInvoice::generateInvoiceNumber(),
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;

            foreach ($request->items as $item) {
                $normalized = $this->normalizeItem($item);
                $product = $this->resolveProduct($item, $normalized);
                $oldPrice = $product->price;

                $invoiceItem = PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $purchaseInvoice->id,
                    'product_id' => $product->id,
                    'quantity' => $normalized['quantity'],
                    'cost_price' => $normalized['cost_price'],
                    'sales_price' => $normalized['sales_price'],
                    'total' => $normalized['quantity'] * $normalized['cost_price'],
                ]);

                $totalAmount += $invoiceItem->total;

                $this->applyPurchaseToProduct($product, $normalized, $oldPrice, $purchaseInvoice);

                if (!empty($item['vehicles'])) {
                    $this->createVehiclesForItem($invoiceItem, $purchaseInvoice, $item['vehicles']);
                }
            }

            $purchaseInvoice->update(['total_amount' => $totalAmount]);

            if ($request->withdraw_from_cashbox) {
                $this->createCashboxTransaction($purchaseInvoice, $totalAmount, $request->currency);
            }

            DB::commit();

            LogService::createLog(
                'PurchaseInvoice',
                'Created',
                $purchaseInvoice->id,
                [],
                $purchaseInvoice->load('items')->toArray(),
                'success'
            );

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
            ->select('id', 'name', 'barcode', 'price', 'price_cost', 'quantity', 'image')
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
     * Display the specified resource.
     */
    public function show(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['supplier', 'creator', 'items.product', 'items.vehicles', 'vehicles']);
        
        return Inertia::render('PurchaseInvoices/Show', [
            'invoice' => $purchaseInvoice,
            'translations' => __('messages'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseInvoice $purchaseInvoice)
    {
        $suppliers = Supplier::where('is_active', true)
            ->select('id', 'name', 'phone')
            ->orderBy('name')
            ->get();

        $products = Product::where('is_active', true)
            ->select('id', 'name', 'price', 'price_cost', 'quantity', 'barcode', 'image')
            ->orderBy('name')
            ->get();

        $purchaseInvoice->load(['supplier', 'items.product']);

        return Inertia::render('PurchaseInvoices/Edit', [
            'invoice' => $purchaseInvoice,
            'suppliers' => $suppliers,
            'products' => $products,
            'translations' => __('messages'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_name' => 'required_without:items.*.product_id|nullable|string|max:255',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.cost_price' => 'nullable|numeric|min:0',
            'items.*.sales_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // Update invoice
            $purchaseInvoice->update([
                'supplier_id' => $request->supplier_id,
                'invoice_date' => $request->invoice_date,
                'notes' => $request->notes,
            ]);

            // Revert old items quantities
            foreach ($purchaseInvoice->items as $oldItem) {
                $product = Product::find($oldItem->product_id);
                if ($product) {
                    $product->decrement('quantity', $oldItem->quantity);
                }
            }

            // Delete old items
            $purchaseInvoice->items()->delete();

            $totalAmount = 0;

            // Create new items and update products
            foreach ($request->items as $item) {
                $normalized = $this->normalizeItem($item);
                $product = $this->resolveProduct($item, $normalized);

                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $purchaseInvoice->id,
                    'product_id' => $product->id,
                    'quantity' => $normalized['quantity'],
                    'cost_price' => $normalized['cost_price'],
                    'sales_price' => $normalized['sales_price'],
                    'total' => $normalized['quantity'] * $normalized['cost_price'],
                ]);

                $product->increment('quantity', $normalized['quantity']);

                $updates = [];
                if ($normalized['cost_price'] > 0) {
                    $updates['price_cost'] = $normalized['cost_price'];
                }
                if ($normalized['sales_price'] > 0) {
                    $updates['price'] = $normalized['sales_price'];
                }
                if (!empty($updates)) {
                    $product->update($updates);
                }

                $totalAmount += $normalized['quantity'] * $normalized['cost_price'];
            }

            // Update total amount
            $purchaseInvoice->update(['total_amount' => $totalAmount]);

            DB::commit();

            return redirect()->route('purchase-invoices.index')
                ->with('success', 'تم تحديث فاتورة المشتريات بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withErrors([
                'error' => 'حدث خطأ أثناء تحديث الفاتورة: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseInvoice $purchaseInvoice)
    {
        DB::beginTransaction();

        try {
            // Revert product quantities
            foreach ($purchaseInvoice->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->decrement('quantity', $item->quantity);
                }
            }

            // Delete vehicles linked to this invoice
            Vehicle::where('purchase_invoice_id', $purchaseInvoice->id)->delete();

            // Delete items
            $purchaseInvoice->items()->delete();

            // Delete related cashbox transactions if any
            CashboxTransaction::where('reference_type', PurchaseInvoice::class)
                ->where('reference_id', $purchaseInvoice->id)
                ->delete();

            $invoiceData = $purchaseInvoice->toArray();
            $invoiceId = $purchaseInvoice->id;

            // Delete invoice
            $purchaseInvoice->delete();

            DB::commit();

            LogService::createLog(
                'PurchaseInvoice',
                'Deleted',
                $invoiceId,
                $invoiceData,
                [],
                'danger'
            );

            return redirect()->route('purchase-invoices.index')
                ->with('success', 'تم حذف فاتورة المشتريات بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withErrors([
                'error' => 'حدث خطأ أثناء حذف الفاتورة: ' . $e->getMessage()
            ]);
        }
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
            (int) round($amount), // Convert to integer
            "شراء منتجات - فاتورة رقم: {$purchaseInvoice->invoice_number}",
            $mainBox->id,
            $mainBox->id,
            'App\Models\User',
            0,
            0,
            $currency,
            strtotime($purchaseInvoice->invoice_date)
        );

        // Create cashbox transaction record
        CashboxTransaction::create([
            'type' => 'withdrawal',
            'amount' => round($amount, 2),
            'currency' => $currency,
            'description' => "شراء منتجات - فاتورة رقم: {$purchaseInvoice->invoice_number}",
            'reference_type' => PurchaseInvoice::class,
            'reference_id' => $purchaseInvoice->id,
            'user_id' => auth()->id(),
            'from_wallet_id' => $mainBox->wallet->id,
            'to_wallet_id' => null,
            'transaction_date' => $purchaseInvoice->invoice_date->format('Y-m-d'),
        ]);

        return $transaction;
    }

    private function normalizeItem(array $item): array
    {
        return [
            'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
            'cost_price' => (float) ($item['cost_price'] ?? 0),
            'sales_price' => (float) ($item['sales_price'] ?? 0),
        ];
    }

    private function resolveProduct(array $item, array $normalized): Product
    {
        if (!empty($item['product_id'])) {
            return Product::findOrFail($item['product_id']);
        }

        $name = trim($item['product_name'] ?? '');
        if ($name === '') {
            throw new \InvalidArgumentException('اسم المنتج مطلوب');
        }

        $product = Product::where('name', $name)->first();

        if (!$product) {
            $cost = $normalized['cost_price'];
            $sales = $normalized['sales_price'];

            $product = Product::create([
                'name' => $name,
                'quantity' => 0,
                'price' => $sales > 0 ? $sales : $cost,
                'price_cost' => $cost > 0 ? $cost : $sales,
                'is_active' => true,
            ]);
        }

        return $product;
    }

    private function applyPurchaseToProduct(Product $product, array $normalized, $oldPrice, PurchaseInvoice $purchaseInvoice): void
    {
        $updates = ['quantity' => $product->quantity + $normalized['quantity']];

        if ($normalized['cost_price'] > 0) {
            $updates['price_cost'] = $normalized['cost_price'];
        }
        if ($normalized['sales_price'] > 0) {
            $updates['price'] = $normalized['sales_price'];
        }

        $product->update($updates);

        if ($normalized['sales_price'] > 0 && $oldPrice != $normalized['sales_price']) {
            ProductPriceHistory::create([
                'product_id' => $product->id,
                'old_price' => $oldPrice,
                'new_price' => $normalized['sales_price'],
                'change_reason' => 'purchase',
                'changed_by' => auth()->id(),
                'purchase_invoice_id' => $purchaseInvoice->id,
            ]);
        }
    }

    private function createVehiclesForItem(PurchaseInvoiceItem $invoiceItem, PurchaseInvoice $purchaseInvoice, array $vehicles): void
    {
        foreach ($vehicles as $vehicleData) {
            $vin = strtoupper(trim($vehicleData['vin']));

            if (Vehicle::where('vin', $vin)->exists()) {
                throw new \Exception("رقم الشانصي {$vin} مسجل مسبقاً");
            }

            Vehicle::create([
                'vin' => $vin,
                'color' => $vehicleData['color'] ?? null,
                'vehicle_model' => $vehicleData['vehicle_model'] ?? null,
                'make' => $vehicleData['make'] ?? null,
                'year' => $vehicleData['year'] ?? null,
                'product_id' => $invoiceItem->product_id,
                'purchase_invoice_id' => $purchaseInvoice->id,
                'purchase_invoice_item_id' => $invoiceItem->id,
                'status' => 'available',
            ]);
        }
    }
}
