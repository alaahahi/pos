<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    /** @var array<string, mixed> */
    protected array $filters;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        $sort = $this->filters['sort'] ?? 'created';

        $query = Product::query()
            ->with('category')
            ->indexFilters($this->filters);

        switch ($sort) {
            case 'name':
                $query->orderBy('products.name', 'asc');
                break;
            case 'price':
                $query->orderBy('products.price', 'desc');
                break;
            case 'quantity':
                $query->orderBy('products.quantity', 'desc');
                break;
            case 'best_selling':
                $query->leftJoin(DB::raw('(SELECT product_id, SUM(quantity) as total_quantity FROM order_items GROUP BY product_id) as order_items'), 'products.id', '=', 'order_items.product_id')
                    ->select('products.*', DB::raw('COALESCE(order_items.total_quantity, 0) as total_sales'))
                    ->orderBy('total_sales', 'desc');
                break;
            case 'created':
            default:
                $query->orderBy('products.created_at', 'desc');
                break;
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'المعرف',
            'الاسم',
            'الموديل',
            'OE',
            'الباركود',
            'الكمية',
            'سعر الوحدة',
            'العملة',
            'القيمة الإجمالية (كمية × سعر)',
            'التصنيف',
            'نشط',
            'تاريخ الإنشاء',
        ];
    }

    /**
     * @param  Product  $product
     */
    public function map($product): array
    {
        $qty = (int) ($product->quantity ?? 0);
        $price = (float) ($product->price ?? 0);
        $line = $qty * $price;

        return [
            $product->id,
            $product->name,
            $product->model ?? '',
            $product->oe_number ?? '',
            $product->barcode ?? '',
            $qty,
            $price,
            $product->currency ?? 'IQD',
            round($line, 2),
            $product->category?->name ?? '',
            $product->is_active ? 'نعم' : 'لا',
            $product->created_at ? $product->created_at->format('Y-m-d H:i') : '',
        ];
    }
}
