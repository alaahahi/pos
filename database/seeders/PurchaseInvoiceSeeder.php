<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\User;

class PurchaseInvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = Supplier::all();
        $products = Product::all();
        $users = User::where('is_active', true)->get();

        if ($suppliers->isEmpty() || $products->isEmpty() || $users->isEmpty()) {
            $this->command->warn('يجب إنشاء الموردين والمنتجات والمستخدمين أولاً!');
            return;
        }

        // Create 5-8 purchase invoices
        $invoiceCount = rand(5, 8);
        
        for ($i = 0; $i < $invoiceCount; $i++) {
            $supplier = $suppliers->random();
            $user = $users->random();
            
            // Create purchase invoice
            $invoice = PurchaseInvoice::create([
                'supplier_id' => $supplier->id,
                'total_amount' => 0, // Will be calculated from items
                'invoice_date' => now()->subDays(rand(1, 60)),
                'notes' => 'فاتورة شراء رقم ' . ($i + 1),
                'invoice_number' => PurchaseInvoice::generateInvoiceNumber(),
                'created_by' => $user->id,
            ]);

            // Create 2-5 items for each invoice
            $itemCount = rand(2, 5);
            $totalAmount = 0;
            
            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $quantity = rand(1, 20);
                $costPrice = $product->price_cost ?? rand(1000, 10000);
                $salesPrice = $costPrice * 1.3; // 30% markup
                $itemTotal = $quantity * $costPrice;
                
                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'cost_price' => $costPrice,
                    'sales_price' => $salesPrice,
                    'total' => $itemTotal,
                ]);
                
                $totalAmount += $itemTotal;
            }
            
            // Update invoice total
            $invoice->update(['total_amount' => $totalAmount]);
        }

        $this->command->info('تم إنشاء فواتير الشراء بنجاح!');
    }
}
