<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق من صحة الفاتورة #{{ $order->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #f8f9fa;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .logo-section {
            margin-bottom: 20px;
        }
        
        .company-logo {
            max-height: 80px;
            max-width: 250px;
            margin-bottom: 15px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .company-contact {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .verification-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .verification-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .status-section {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .status-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .status-valid {
            color: #28a745;
        }
        
        .status-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .status-valid .status-title {
            color: #28a745;
        }
        
        .status-description {
            font-size: 16px;
            color: #666;
        }
        
        .invoice-details {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .details-title {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        
        .detail-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }
        
        .detail-value {
            color: #333;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 12px;
        }
        
        .status-created { background: #6c757d; color: white; }
        .status-received { background: #17a2b8; color: white; }
        .status-executing { background: #007bff; color: white; }
        .status-partial_payment { background: #ffc107; color: black; }
        .status-full_payment { background: #28a745; color: white; }
        .status-completed { background: #28a745; color: white; }
        .status-cancelled { background: #dc3545; color: white; }
        
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            border-top: 1px solid #dee2e6;
        }
        
        .verification-info {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-right: 4px solid #007bff;
        }
        
        .verification-info h4 {
            color: #007bff;
            margin-bottom: 10px;
        }
        
        .verification-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .content {
                padding: 20px;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo-section">
                @if($companyInfo['logo'])
                <img src="{{ asset($companyInfo['logo']) }}" alt="Company Logo" class="company-logo">
                @endif
                <div class="company-name">{{ $companyInfo['name'] }}</div>
                @if($companyInfo['phone'] || $companyInfo['email'] || $companyInfo['address'])
                <div class="company-contact">
                    @if($companyInfo['phone'])
                        <span>📞 {{ $companyInfo['phone'] }}</span>
                    @endif
                    @if($companyInfo['email'])
                        <span>✉️ {{ $companyInfo['email'] }}</span>
                    @endif
                    @if($companyInfo['address'])
                        <span>📍 {{ $companyInfo['address'] }}</span>
                    @endif
                </div>
                @endif
            </div>
            <div class="verification-title">التحقق من صحة الفاتورة</div>
            <div class="verification-subtitle">Invoice Verification</div>
        </div>
        
        <!-- Content -->
        <div class="content">
            <!-- Status Section -->
            <div class="status-section">
                <div class="status-icon status-valid">✅</div>
                <div class="status-title status-valid">فاتورة صحيحة</div>
                <div class="status-description">تم التحقق من صحة هذه الفاتورة بنجاح</div>
            </div>
            
            <!-- Verification Info -->
            <div class="verification-info">
                <h4>معلومات التحقق</h4>
                <p><strong>رقم الفاتورة:</strong> #{{ $order->id }}</p>
                <p><strong>تاريخ التحقق:</strong> {{ now()->format('Y-m-d H:i:s') }}</p>
                <p><strong>حالة الفاتورة:</strong> <span class="status-badge status-{{ $order->status }}">{{ $order->status_name }}</span></p>
            </div>
            
            <!-- Invoice Details -->
            <div class="invoice-details">
                <div class="details-title">تفاصيل الفاتورة</div>
                <div class="details-grid">
                    <div class="detail-item">
                        <div class="detail-label">رقم الطلب</div>
                        <div class="detail-value">#{{ $order->id }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">تاريخ الإنشاء</div>
                        <div class="detail-value">{{ $order->created_at->format('Y-m-d H:i') }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">اسم العميل</div>
                        <div class="detail-value">{{ $order->customer_name }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">هاتف العميل</div>
                        <div class="detail-value">{{ $order->customer_phone }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">نوع الديكور</div>
                        <div class="detail-value">{{ $order->decoration->name }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">تاريخ الحدث</div>
                        <div class="detail-value">{{ $order->event_date->format('Y-m-d') }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">المبلغ الإجمالي</div>
                        <div class="detail-value">{{ number_format($order->total_price, 2) }} {{ $order->currency == 'dollar' ? 'دولار' : 'دينار' }}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">المبلغ المدفوع</div>
                        <div class="detail-value">{{ number_format($order->paid_amount ?? 0, 2) }} {{ $order->currency == 'dollar' ? 'دولار' : 'دينار' }}</div>
                    </div>
                    @if($order->assignedEmployee)
                    <div class="detail-item">
                        <div class="detail-label">الموظف المسند</div>
                        <div class="detail-value">{{ $order->assignedEmployee->name }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>هذه الصفحة تؤكد صحة الفاتورة رقم #{{ $order->id }}</p>
            <p>تم إنشاء هذه الصفحة في {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
