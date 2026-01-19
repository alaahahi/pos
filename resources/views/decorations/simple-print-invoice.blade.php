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
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
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
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid #10b981;
        }
        
        .info-section h3 {
            color: #10b981;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: bold;
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
        
        .decoration-card {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .decoration-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .decoration-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .pricing-section {
            background: white;
            border: 2px solid #10b981;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .pricing-title {
            font-size: 18px;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .pricing-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .pricing-table td {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .pricing-table tr:last-child td {
            border-bottom: none;
        }
        
        .pricing-table .total-row {
            background: #e8f5e9;
            font-weight: bold;
            font-size: 14px;
        }
        
        .pricing-table .total-row td {
            border-top: 2px solid #10b981;
            padding-top: 12px;
        }
        
        .currency-symbol {
            font-weight: bold;
            color: #10b981;
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
            background: #fff8dc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }
        
        .notes-title {
            font-size: 14px;
            font-weight: bold;
            color: #f59e0b;
            margin-bottom: 8px;
        }
        
        .notes-content {
            background: white;
            padding: 10px;
            border-radius: 6px;
            min-height: 40px;
            white-space: pre-wrap;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 15px;
            border-top: 2px solid #10b981;
            color: #666;
            font-size: 11px;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .print-button:hover {
            background: #059669;
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
    <button class="print-button" onclick="window.print()">🖨️ طباعة الفاتورة</button>
    
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="header-top">
                <div class="invoice-number">رقم الطلب: #{{ $order->id }}</div>
                <div class="invoice-title">🎨 فاتورة طلب ديكور</div>
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
        
        <!-- Decoration Card -->
        <div class="decoration-card">
            <div class="decoration-icon">🎊</div>
            <div class="decoration-name">{{ $order->decoration_name }}</div>
        </div>
        
        <!-- Invoice Info -->
        <div class="invoice-info">
            <!-- Customer Info -->
            <div class="info-section">
                <h3>👤 معلومات العميل</h3>
                <div class="info-item">
                    <span class="info-label">الاسم:</span>
                    <span class="info-value">{{ $order->customer_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">الهاتف:</span>
                    <span class="info-value">{{ $order->customer_phone }}</span>
                </div>
            </div>
            
            <!-- Event Info -->
            <div class="info-section">
                <h3>📅 معلومات المناسبة</h3>
                <div class="info-item">
                    <span class="info-label">التاريخ:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($order->event_date)->format('Y-m-d') }}</span>
                </div>
                @if($order->event_time)
                <div class="info-item">
                    <span class="info-label">الساعة:</span>
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
            <h3>🔧 الموظف المسؤول</h3>
            <div class="info-item">
                <span class="info-label">الاسم:</span>
                <span class="info-value">{{ $order->assignedEmployee->name }}</span>
            </div>
        </div>
        @endif
        
        <!-- Pricing Section -->
        <div class="pricing-section">
            <div class="pricing-title">💰 تفاصيل الدفع</div>
            <table class="pricing-table">
                <tbody>
                    <tr class="total-row">
                        <td><strong>💵 المبلغ الإجمالي</strong></td>
                        <td style="text-align: left;"><strong>{{ number_format($order->total_price, 2) }} $</strong></td>
                    </tr>
                    <tr>
                        <td>✅ المبلغ المدفوع</td>
                        <td style="text-align: left; color: #10b981;">{{ number_format($order->paid_amount ?? 0, 2) }} $</td>
                    </tr>
                    <tr style="background: #fff3cd;">
                        <td><strong>⏳ المبلغ المتبقي</strong></td>
                        <td style="text-align: left; color: #f59e0b;"><strong>{{ number_format($order->total_price - ($order->paid_amount ?? 0), 2) }} $</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Special Requests -->
        @if($order->special_requests)
        <div class="notes-section">
            <div class="notes-title">📝 ملاحظات خاصة</div>
            <div class="notes-content">{{ $order->special_requests }}</div>
        </div>
        @endif
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>شكراً لاختياركم خدماتنا</strong></p>
            <p style="margin-top: 5px; font-size: 10px;">تمت الطباعة في: {{ now()->format('Y-m-d H:i') }}</p>
        </div>
    </div>
</body>
</html>
