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
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
            position: relative;
            overflow: hidden;
        }
        
        .invoice-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, {{ env('PRIMARY_COLOR', '#007bff') }}, #28a745, #ffc107, #dc3545);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .header-left {
            flex: 1;
        }
        
        .header-right {
            text-align: right;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 6px;
        }
        
        .header-center {
            text-align: center;
            margin: 8px 0;
        }
        
        .company-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            margin-top: 8px;
        }
        
        .company-info-item {
            background: white;
            padding: 6px;
            border-radius: 5px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 2px solid {{ env('PRIMARY_COLOR', '#007bff') }};
            transition: transform 0.2s ease;
        }
        
        .company-info-item:hover {
            transform: translateY(-2px);
        }
        
        .company-info-item .icon {
            font-size: 12px;
            margin-bottom: 3px;
            display: block;
        }
        
        .company-info-item .text {
            font-size: 9px;
            color: #333;
            font-weight: 600;
        }
        
     
        
        .company-logo {
            max-height: 100px;
            max-width: 120px;
         }
        
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: {{ env('PRIMARY_COLOR', '#007bff') }};
            margin-bottom: 2px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .company-tagline {
            font-size: 12px;
            color: #666;
            font-style: italic;
        }
        
        .company-contact {
            font-size: 12px;
            color: #555;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .invoice-title {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 4px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        .invoice-number {
            font-size: 10px;
            color: #666;
            font-weight: 500;
            background: #f8f9fa;
            padding: 3px 8px;
            border-radius: 8px;
            display: inline-block;
            border: 1px solid #dee2e6;
        }
        
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 6px;
        }
        
        .info-section {
            flex: 1;
            min-width: 200px;
        }
        
        .info-section h3 {
            background: {{ env('PRIMARY_COLOR', '#007bff') }};
            color: white;
            padding: 6px;
            margin-bottom: 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }
        
        .info-item {
            margin-bottom: 2px;
            display: flex;
            justify-content: space-between;
            font-size: 9px;
        }
        
        .info-label {
            font-weight: bold;
            color: #555;
        }
        
        .info-value {
            color: #333;
        }
        
        .decoration-details {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 12px;
            border: 1px solid #dee2e6;
        }
        
        .decoration-title {
            font-size: 14px;
            font-weight: bold;
            color: {{ env('PRIMARY_COLOR', '#007bff') }};
            margin-bottom: 8px;
            text-align: center;
        }
        
        .decoration-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 8px;
        }
        
        .decoration-item {
            background: white;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .decoration-item h4 {
            color: {{ env('PRIMARY_COLOR', '#007bff') }};
            margin-bottom: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .decoration-item p {
            font-size: 10px;
            margin: 0;
        }
        
        .pricing-section {
            background: white;
            border: 2px solid {{ env('PRIMARY_COLOR', '#007bff') }};
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .pricing-title {
            font-size: 16px;
            font-weight: bold;
            color: {{ env('PRIMARY_COLOR', '#007bff') }};
            margin-bottom: 12px;
            text-align: center;
        }
        
        .pricing-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .pricing-table th,
        .pricing-table td {
            padding: 6px;
            text-align: right;
            border-bottom: 1px solid #dee2e6;
            font-size: 10px;
        }
        
        .pricing-table th {
            background: {{ env('PRIMARY_COLOR', '#007bff') }};
            color: white;
            font-weight: bold;
        }
        
        .pricing-table .total-row {
            background: #f0f4ff;
            font-weight: bold;
            font-size: 11px;
            border: 1px solid {{ env('PRIMARY_COLOR', '#007bff') }};
        }
        
        .pricing-table .total-row td {
            border-top: 1px solid {{ env('PRIMARY_COLOR', '#007bff') }};
            border-bottom: 1px solid {{ env('PRIMARY_COLOR', '#007bff') }};
        }
        
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 9px;
            border: 1px solid;
        }
        
        .status-created { 
            background: #6c757d; 
            color: white; 
            border-color: #6c757d;
        }
        .status-received { 
            background: #17a2b8; 
            color: white; 
            border-color: #17a2b8;
        }
        .status-executing { 
            background: {{ env('PRIMARY_COLOR', '#007bff') }}; 
            color: white; 
            border-color: {{ env('PRIMARY_COLOR', '#007bff') }};
        }
        .status-partial_payment { 
            background: #ffc107; 
            color: #212529; 
            border-color: #ffc107;
        }
        .status-full_payment { 
            background: #28a745; 
            color: white; 
            border-color: #28a745;
        }
        .status-completed { 
            background: #28a745; 
            color: white; 
            border-color: #28a745;
        }
        .status-cancelled { 
            background: #dc3545; 
            color: white; 
            border-color: #dc3545;
        }
        
        .notes-section {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
        }
        
        .notes-title {
            font-size: 12px;
            font-weight: bold;
            color: {{ env('PRIMARY_COLOR', '#007bff') }};
            margin-bottom: 6px;
        }
        
        .notes-content {
            background: white;
            padding: 8px;
            border-radius: 3px;
            border: 1px solid #dee2e6;
            min-height: 30px;
            font-size: 10px;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            padding: 12px;
            border-top: 1px solid {{ env('PRIMARY_COLOR', '#007bff') }};
            color: #666;
            font-size: 9px;
            background: #f8f9fa;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #0056b3;
        }
        
        @media print {
            .print-button {
                display: none;
            }
            
            body {
                font-size: 11px;
                margin: 0;
                padding: 0;
                background: white !important;
            }
            
            .invoice-container {
                padding: 8mm;
                max-width: none;
                margin: 0;
                min-height: 277mm;
                background: white !important;
            }
            
            .invoice-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: white !important;
                margin-bottom: 15px;
                padding: 20px;
                border-radius: 10px;
                box-shadow: none;
            }
            
            .company-info-item {
                background: rgba(255,255,255,0.2) !important;
                border: 1px solid rgba(255,255,255,0.3) !important;
                backdrop-filter: none;
            }
            
            .company-info-item:hover {
                transform: none;
            }
            
            .qr-code {
                background: rgba(255,255,255,0.95) !important;
                border: 2px solid rgba(255,255,255,0.5) !important;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3) !important;
                backdrop-filter: none;
            }
            
            .info-section h3 {
                background: linear-gradient(135deg, #667eea, #764ba2) !important;
                color: white !important;
                box-shadow: none;
            }
            
            .decoration-details {
                background: #f8f9fa !important;
                border: 1px solid #e9ecef !important;
                box-shadow: none;
                margin-bottom: 15px;
            }
            
            .decoration-item {
                background: white !important;
                border: 1px solid #e9ecef !important;
                box-shadow: none;
            }
            
            .decoration-item:hover {
                transform: none;
            }
            
            .pricing-section {
                background: white !important;
                border: 2px solid #667eea !important;
                box-shadow: none;
                margin-bottom: 15px;
            }
            
            .pricing-table th {
                background: linear-gradient(135deg, #667eea, #764ba2) !important;
                color: white !important;
                text-shadow: none;
            }
            
            .pricing-table .total-row {
                background: #f0f4ff !important;
                border: 1px solid #667eea !important;
            }
            
            .notes-section {
                background: #f8f9fa !important;
                border: 1px solid #e9ecef !important;
                box-shadow: none;
                margin-bottom: 15px;
            }
            
            .footer {
                background: #f8f9fa !important;
                border-top: 1px solid #667eea !important;
                box-shadow: none;
            }
            
            .status-badge {
                box-shadow: none;
            }
        }
        
        .currency-symbol {
            font-weight: bold;
            color: {{ env('PRIMARY_COLOR', '#007bff') }};
        }
        
        .qr-code {
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 12px;
            padding: 8px;
            border: 3px solid {{ env('PRIMARY_COLOR', '#007bff') }};
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .qr-code img {
            width: 100%;
            height: 100%;
            border-radius: 4px;
        }
        
        .qr-label {
            font-size: 10px;
            color: #333;
            text-align: center;
            margin-top: 8px;
            font-weight: bold;
        }
        
        @media print {
            .qr-code {
                width: 90px;
                height: 90px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            }
            
            .qr-label {
                font-size: 7px;
            }
            
            .invoice-header {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
                border-radius: 8px !important;
                padding: 12px !important;
                margin-bottom: 12px !important;
            }
            
            .invoice-header::before {
                content: '' !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                height: 4px !important;
                background: linear-gradient(90deg, {{ env('PRIMARY_COLOR', '#007bff') }}, #28a745, #ffc107, #dc3545) !important;
            }
            
            .company-info-item {
                background: white !important;
                padding: 6px !important;
                border-radius: 5px !important;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1) !important;
                border-left: 2px solid {{ env('PRIMARY_COLOR', '#007bff') }} !important;
            }
            
            .company-logo {
                max-height: 100px !important;
                max-width: 120px !important;
            }
            
            .invoice-title {
                font-size: 18px !important;
                font-weight: bold !important;
                color: #2c3e50 !important;
                margin-bottom: 4px !important;
            }
            
            .pricing-section {
                background: white !important;
                border: 2px solid {{ env('PRIMARY_COLOR', '#007bff') }} !important;
                border-radius: 6px !important;
                padding: 15px !important;
            }
            
            .pricing-table th {
                background: {{ env('PRIMARY_COLOR', '#007bff') }} !important;
                color: white !important;
                font-weight: bold !important;
            }
            
            .decoration-details {
                background: #f8f9fa !important;
                padding: 12px !important;
                border-radius: 6px !important;
                border: 1px solid #dee2e6 !important;
            }
            
            .notes-section {
                background: #f8f9fa !important;
                padding: 12px !important;
                border-radius: 5px !important;
                border: 1px solid #dee2e6 !important;
            }
            
            .footer {
                background: #f8f9fa !important;
                border-top: 1px solid {{ env('PRIMARY_COLOR', '#007bff') }} !important;
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
                <div class="header-left">
                    <div class="logo-section">
                        @if($companyInfo['logo'])
                        <img src="{{ asset($companyInfo['logo']) }}" alt="Company Logo" class="company-logo">
                        @endif
                  
                    </div>
                </div>
                <div class="header-right">
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" alt="QR Code">
                    </div>
                </div>
            </div>
            
            <div class="header-center">
            <div class="invoice-title">فاتورة طلب ديكور</div>
            </div>
            
            <div class="company-info-grid">
                @if($companyInfo['phone'])
                <div class="company-info-item">
                    <span class="icon">📞</span>
                    <span class="text">{{ $companyInfo['phone'] }}</span>
                </div>
                @endif
                @if($companyInfo['email'])
                <div class="company-info-item">
                    <span class="icon">✉️</span>
                    <span class="text">{{ $companyInfo['email'] }}</span>
                </div>
                @endif
                @if($companyInfo['address'])
                <div class="company-info-item">
                    <span class="icon">📍</span>
                    <span class="text">{{ $companyInfo['address'] }}</span>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Invoice Info -->
        <div class="invoice-info">
            <!-- Order Info -->
            <div class="info-section">
                <h3>معلومات الطلب</h3>
                <div class="info-item">
                    <span class="info-label">رقم الطلب:</span>
                    <span class="info-value">#{{ $order->id }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">تاريخ الإنشاء:</span>
                    <span class="info-value">{{ $order->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">الحالة:</span>
                    <span class="status-badge status-{{ $order->status }}">{{ $order->status_name }}</span>
                </div>
                @if($order->assignedEmployee)
                <div class="info-item">
                    <span class="info-label">الموظف المسند:</span>
                    <span class="info-value">{{ $order->assignedEmployee->name }}</span>
                </div>
                @endif
            </div>
            
            <!-- Customer Info -->
            <div class="info-section">
                <h3>معلومات العميل</h3>
                <div class="info-item">
                    <span class="info-label">الاسم:</span>
                    <span class="info-value">{{ $order->customer_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">الهاتف:</span>
                    <span class="info-value">{{ $order->customer_phone }}</span>
                </div>
                @if($order->customer_email)
                <div class="info-item">
                    <span class="info-label">البريد الإلكتروني:</span>
                    <span class="info-value">{{ $order->customer_email }}</span>
                </div>
                @endif
                @if($order->customer && $order->customer->address)
                <div class="info-item">
                    <span class="info-label">العنوان:</span>
                    <span class="info-value">{{ $order->customer->address }}</span>
                </div>
                @endif
            </div>
            
            <!-- Event Info -->
            <div class="info-section">
                <h3>معلومات الحدث</h3>
                <div class="info-item">
                    <span class="info-label">تاريخ الحدث:</span>
                    <span class="info-value">{{ $order->event_date->format('Y-m-d') }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">وقت الحدث:</span>
                    <span class="info-value">{{ $order->event_time }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">عدد الضيوف:</span>
                    <span class="info-value">{{ $order->guest_count }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">عنوان الحدث:</span>
                    <span class="info-value">{{ $order->event_address }}</span>
                </div>
            </div>
        </div>
        
        <!-- Decoration Details -->
        <div class="decoration-details">
            <div class="decoration-title">تفاصيل الديكور</div>
            <div class="decoration-info">
                <div class="decoration-item">
                    <h4>اسم الديكور</h4>
                    <p>{{ $order->decoration->name }}</p>
                </div>
                <div class="decoration-item">
                    <h4>نوع الديكور</h4>
                    <p>{{ $order->decoration->type_name }}</p>
                </div>
                <div class="decoration-item">
                    <h4>المدة</h4>
                    <p>{{ $order->decoration->duration_hours }} ساعة</p>
                </div>
                <div class="decoration-item">
                    <h4>حجم الفريق</h4>
                    <p>{{ $order->decoration->team_size }} عضو</p>
                </div>
                @if($order->decoration->description)
                <div class="decoration-item" style="grid-column: 1 / -1;">
                    <h4>الوصف</h4>
                    <p>{{ $order->decoration->description }}</p>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Pricing Section -->
        <div class="pricing-section">
            <table class="pricing-table">
                <thead>
                    <tr>
                        <th>الوصف</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>السعر الأساسي</td>
                        <td>{{ number_format($order->base_price, 2) }} <span class="currency-symbol">{{ $order->currency == 'dollar' ? 'دولار' : 'دينار' }}</span></td>
                    </tr>
                    @if($order->additional_cost > 0)
                    <tr>
                        <td>التكلفة الإضافية</td>
                        <td>+{{ number_format($order->additional_cost, 2) }} <span class="currency-symbol">{{ $order->currency == 'dollar' ? 'دولار' : 'دينار' }}</span></td>
                    </tr>
                    @endif
                    @if($order->discount > 0)
                    <tr>
                        <td>الخصم</td>
                        <td>-{{ number_format($order->discount, 2) }} <span class="currency-symbol">{{ $order->currency == 'dollar' ? 'دولار' : 'دينار' }}</span></td>
                    </tr>
                    @endif
                    <tr class="total-row">
                        <td><strong>المجموع الإجمالي</strong></td>
                        <td><strong>{{ number_format($order->total_price, 2) }} <span class="currency-symbol">{{ $order->currency == 'dollar' ? 'دولار' : 'دينار' }}</span></strong></td>
                    </tr>
                    <tr>
                        <td>المبلغ المدفوع</td>
                        <td>{{ number_format($order->paid_amount ?? 0, 2) }} <span class="currency-symbol">{{ $order->currency == 'dollar' ? 'دولار' : 'دينار' }}</span></td>
                    </tr>
                    <tr>
                        <td>المبلغ المتبقي</td>
                        <td>{{ number_format($order->total_price - ($order->paid_amount ?? 0), 2) }} <span class="currency-symbol">{{ $order->currency == 'dollar' ? 'دولار' : 'دينار' }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Special Requests -->
        @if($order->special_requests)
        <div class="notes-section">
            <div class="notes-title">طلبات خاصة</div>
            <div class="notes-content">{{ $order->special_requests }}</div>
        </div>
        @endif
        
        <!-- Selected Items -->
        @if($order->selected_items && count($order->selected_items) > 0)
        <div class="notes-section">
            <div class="notes-title">العناصر المختارة</div>
            <div class="notes-content">
                <ul style="margin: 0; padding-right: 20px;">
                    @foreach($order->selected_items as $item)
                    <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
        
        <!-- Notes -->
        @if($order->notes)
        <div class="notes-section">
            <div class="notes-title">ملاحظات</div>
            <div class="notes-content">{{ $order->notes }}</div>
        </div>
        @endif
        
        <!-- Footer -->
        <div class="footer">
            <p>شكراً لاختياركم خدماتنا</p>
        </div>
    </div>
</body>
</html>

