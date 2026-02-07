<?php

namespace App\Exports;

use App\Models\SimpleDecorationOrder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SimpleDecorationOrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    protected $filters;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Build query with filters and return collection
     */
    public function collection()
    {
        $query = SimpleDecorationOrder::with(['assignedEmployee'])
            ->when($this->filters['status'] ?? null, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($this->filters['employee'] ?? null, function ($query, $employeeId) {
                return $query->where('assigned_employee_id', $employeeId);
            })
            ->when($this->filters['currency'] ?? null, function ($query, $currency) {
                return $query->where('currency', $currency);
            })
            ->when($this->filters['search'] ?? null, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'LIKE', "%{$search}%")
                      ->orWhere('customer_phone', 'LIKE', "%{$search}%")
                      ->orWhere('decoration_name', 'LIKE', "%{$search}%");
                });
            })
            ->when($this->filters['date_from'] ?? null, function ($query, $dateFrom) {
                return $query->whereDate('event_date', '>=', $dateFrom);
            })
            ->when($this->filters['date_to'] ?? null, function ($query, $dateTo) {
                return $query->whereDate('event_date', '<=', $dateTo);
            })
            ->latest();

        return $query->get();
    }

    /**
     * Map data for each row
     */
    public function map($order): array
    {
        return [
            $order->id,
            $order->decoration_name ?? 'غير محدد',
            $order->customer_name,
            $order->customer_phone,
            $order->assigned_employee ? $order->assigned_employee->name : 'غير معين',
            $order->currency ?? 'dollar',
            $order->total_price,
            $order->paid_amount ?? 0,
            $order->total_price - ($order->paid_amount ?? 0), // المتبقي
            $order->event_date,
            $order->event_time ?? 'غير محدد',
            $this->getStatusText($order->status),
            $order->special_requests ?? '-',
            $order->created_at->format('Y-m-d H:i'),
        ];
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [
            '#',
            'اسم الديكور',
            'اسم الزبون',
            'رقم الهاتف',
            'المنجز',
            'العملة',
            'السعر الكلي',
            'المدفوع',
            'المتبقي',
            'تاريخ المناسبة',
            'ساعة المناسبة',
            'الحالة',
            'ملاحظات',
            'تاريخ الإنشاء',
        ];
    }

    /**
     * Apply styles to worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the header row
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '667eea'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Set column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 8,   // #
            'B' => 25,  // اسم الديكور
            'C' => 20,  // اسم الزبون
            'D' => 15,  // رقم الهاتف
            'E' => 15,  // المنجز
            'F' => 12,  // العملة
            'G' => 15,  // السعر الكلي
            'H' => 15,  // المدفوع
            'I' => 15,  // المتبقي
            'J' => 15,  // تاريخ المناسبة
            'K' => 15,  // ساعة المناسبة
            'L' => 15,  // الحالة
            'M' => 30,  // ملاحظات
            'N' => 20,  // تاريخ الإنشاء
        ];
    }

    /**
     * Get status text in Arabic
     */
    protected function getStatusText($status)
    {
        $statuses = [
            'created' => 'تم الإنشاء',
            'received' => 'تم الاستلام',
            'executing' => 'قيد التنفيذ',
            'partial_payment' => 'دفعة جزئية',
            'full_payment' => 'دفعة كاملة',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
        ];
        
        return $statuses[$status] ?? $status;
    }
}
