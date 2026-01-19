<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة طلب ديكور #{{ $order->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: white;
            margin: 0;
            padding: 0;
        }
        
        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 15mm;
            background: white;
            min-height: 297mm;
            box-sizing: border-box;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .invoice-number {
            font-size: 14px;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-block;
            border: 2px solid rgba(255,255,255,0.5);
        }
        
        .company-info {
            display: flex;
            justify-content: space-around;
            gap: 10px;
            margin-top: 15px;
        }
        
        .company-info-item {
            background: rgba(255,255,255,0.2);
            padding: 8px;
            border-radius: 8px;
            text-align: center;
            flex: 1;
        }
        
        .company-info-item .icon {
            font-size: 16px;
            display: block;
            margin-bottom: 5px;
        }
        
        .company-info-item .text {
            font-size: 11px;
        }
        
        .invoice-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-section {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 15px;
            border-left: 4px solid #1e40af;
        }
        
        .info-section h3 {
            color: #1e40af;
            margin-bottom: 12px;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 11px;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
        }
        
        .info-value {
            color: #333;
        }
        
        .pricing-section {
            background: white;
            border: 2px solid #1e40af;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .pricing-title {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 15px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .pricing-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .pricing-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 12px;
        }
        
        .pricing-table tr:last-child td {
            border-bottom: none;
        }
        
        .pricing-table .total-row {
            background: #dbeafe;
            font-weight: bold;
            font-size: 13px;
        }
        
        .pricing-table .total-row td {
            border-top: 2px solid #1e40af;
            padding-top: 12px;
        }
        
        .currency-symbol {
            font-weight: bold;
            color: #1e40af;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 10px;
        }
        
        .status-created { background: #6c757d; color: white; }
        .status-received { background: #17a2b8; color: white; }
        .status-executing { background: #007bff; color: white; }
        .status-partial_payment { background: #ffc107; color: #212529; }
        .status-full_payment { background: #28a745; color: white; }
        .status-completed { background: #28a745; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
        
        .notes-section {
            background: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #64748b;
            border: 1px solid #e2e8f0;
        }
        
        .notes-title {
            font-size: 13px;
            font-weight: bold;
            color: #475569;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .notes-content {
            background: white;
            padding: 12px;
            border-radius: 4px;
            min-height: 40px;
            white-space: pre-wrap;
            color: #334155;
            line-height: 1.6;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            border-top: 2px solid #1e40af;
            color: #666;
            font-size: 11px;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #1e40af;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            z-index: 1000;
            box-shadow: 0 2px 8px rgba(30, 64, 175, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .print-button:hover {
            background: #1e3a8a;
        }
        
        @media print {
            .print-button {
                display: none;
            }
            
            body {
                font-size: 11px;
                margin: 0;
                padding: 0;
            }
            
            .invoice-container {
                padding: 5mm;
                max-width: none;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <button class="print-button" onclick="window.print()">طباعة الفاتورة</button>
    
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="header-top">
                <div class="invoice-number">رقم الطلب: #{{ $order->id }}</div>
                <div class="invoice-title">فاتورة طلب ديكور</div>
                <div class="invoice-number">{{ $order->created_at->format('Y-m-d') }}</div>
            </div>
            
            <div class="company-info">
                <div class="company-info-item">
                    <span class="icon">📞</span>
                    <span class="text">{{ env('COMPANY_PHONE', '07XX XXX XXXX') }}</span>
                </div>
                <div class="company-info-item">
                    <span class="icon">✉️</span>
                    <span class="text">{{ env('COMPANY_EMAIL', 'info@company.com') }}</span>
                </div>
                <div class="company-info-item">
                    <span class="icon">📍</span>
                    <span class="text">{{ env('COMPANY_ADDRESS', 'العنوان') }}</span>
                </div>
            </div>
        </div>
        
        <!-- Invoice Info -->
        <div class="invoice-info">
            <!-- Customer Info -->
            <div class="info-section">
                <h3>معلومات العميل</h3>
                <div class="info-item">
                    <span class="info-label">اسم العميل:</span>
                    <span class="info-value">{{ $order->customer_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">رقم الهاتف:</span>
                    <span class="info-value">{{ $order->customer_phone }}</span>
                </div>
            </div>
            
            <!-- Event Info -->
            <div class="info-section">
                <h3>تفاصيل الطلب</h3>
                <div class="info-item">
                    <span class="info-label">نوع الديكور:</span>
                    <span class="info-value">{{ $order->decoration_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">تاريخ المناسبة:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($order->event_date)->format('Y-m-d') }}</span>
                </div>
                @if($order->event_time)
                <div class="info-item">
                    <span class="info-label">ساعة المناسبة:</span>
                    <span class="info-value">{{ $order->event_time }}</span>
                </div>
                @endif
                <div class="info-item">
                    <span class="info-label">الحالة:</span>
                    <span class="status-badge status-{{ $order->status }}">{{ $order->status_label }}</span>
                </div>
            </div>
        </div>
        
        <!-- Assigned Employee -->
        @if($order->assignedEmployee)
        <div class="info-section" style="margin-bottom: 20px;">
            <h3>الموظف المسؤول</h3>
            <div class="info-item">
                <span class="info-label">الاسم:</span>
                <span class="info-value">{{ $order->assignedEmployee->name }}</span>
            </div>
        </div>
        @endif
        
        <!-- Pricing Section -->
        <div class="pricing-section">
            <div class="pricing-title">تفاصيل الدفع</div>
            <table class="pricing-table">
                <tbody>
                    <tr class="total-row">
                        <td><strong>المبلغ الإجمالي</strong></td>
                        <td style="text-align: left;"><strong>{{ number_format($order->total_price, 2) }} $</strong></td>
                    </tr>
                    <tr>
                        <td>المبلغ المدفوع</td>
                        <td style="text-align: left; color: #059669;">{{ number_format($order->paid_amount ?? 0, 2) }} $</td>
                    </tr>
                    <tr style="background: #fef3c7;">
                        <td><strong>المبلغ المتبقي</strong></td>
                        <td style="text-align: left; color: #d97706;"><strong>{{ number_format($order->total_price - ($order->paid_amount ?? 0), 2) }} $</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Special Requests -->
        @if($order->special_requests)
        <div class="notes-section">
            <div class="notes-title">ملاحظات خاصة</div>
            <div class="notes-content">{{ $order->special_requests }}</div>
        </div>
        @endif
        
        <!-- Footer -->
        <div class="footer">
            <p style="font-weight: 600; color: #1e40af; margin-bottom: 8px;">شكراً لثقتكم بخدماتنا</p>
            <p style="font-size: 10px; color: #64748b;">تاريخ الطباعة: {{ now()->format('Y-m-d | H:i') }}</p>
        </div>
    </div>
</body>
</html>
