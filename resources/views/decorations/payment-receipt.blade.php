<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وصل قبض - {{ $payment->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            direction: rtl;
            padding: 20px;
            background: #f5f5f5;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #28a745;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-logo {
            max-width: 150px;
            margin-bottom: 15px;
        }

        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .company-info {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }

        .receipt-title {
            text-align: center;
            font-size: 32px;
            font-weight: bold;
            color: #28a745;
            margin: 30px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .receipt-number {
            text-align: center;
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
        }

        .info-section {
            margin-bottom: 30px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #555;
            width: 40%;
        }

        .info-value {
            color: #333;
            width: 60%;
            text-align: left;
        }

        .amount-box {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin: 30px 0;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .amount-label {
            font-size: 18px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .amount-value {
            font-size: 48px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        .order-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .order-details h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
            border-bottom: 2px solid #28a745;
            padding-bottom: 10px;
        }

        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
        }

        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 60px;
        }

        .signature-box {
            text-align: center;
            width: 45%;
        }

        .signature-line {
            border-top: 2px solid #333;
            margin-top: 50px;
            padding-top: 10px;
            font-weight: 600;
        }

        .notes {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            color: #856404;
        }

        .print-date {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin-top: 20px;
        }

        @media print {
            body {
                padding: 0;
                background: white;
            }
            
            .receipt-container {
                box-shadow: none;
                max-width: 100%;
                padding: 20px;
            }
            
            @page {
                margin: 15mm;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header -->
        <div class="header">
            @if($companyInfo['logo'])
                <img src="{{ asset($companyInfo['logo']) }}" alt="Logo" class="company-logo">
            @endif
            <div class="company-name">{{ $companyInfo['name'] }}</div>
            <div class="company-info">
                @if($companyInfo['phone'])
                    <div>📞 {{ $companyInfo['phone'] }}</div>
                @endif
                @if($companyInfo['email'])
                    <div>✉️ {{ $companyInfo['email'] }}</div>
                @endif
                @if($companyInfo['address'])
                    <div>📍 {{ $companyInfo['address'] }}</div>
                @endif
            </div>
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">وصل قبض</div>
        <div class="receipt-number">رقم الوصل: #{{ $payment->id }}</div>

        <!-- Amount Box -->
        <div class="amount-box">
            <div class="amount-label">المبلغ المستلم</div>
            <div class="amount-value">
                {{ number_format($payment->amount, 2) }} 
                {{ $payment->currency === 'USD' ? 'دولار' : 'دينار' }}
            </div>
        </div>

        <!-- Payment Information -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">رقم الوصل:</div>
                <div class="info-value">#{{ $payment->id }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">التاريخ والوقت:</div>
                <div class="info-value">{{ $payment->created_at->format('Y-m-d h:i A') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">طريقة الدفع:</div>
                <div class="info-value">
                    {{ $details['payment_method'] ?? 'نقدي' }}
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">العملة:</div>
                <div class="info-value">
                    <strong>{{ $payment->currency === 'USD' ? 'دولار أمريكي (USD)' : 'دينار عراقي (IQD)' }}</strong>
                </div>
            </div>
        </div>

        <!-- Order Details -->
        @if($order)
        <div class="order-details">
            <h3>تفاصيل الطلب</h3>
            <div class="info-row">
                <div class="info-label">رقم الطلب:</div>
                <div class="info-value">#{{ $order->id }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">نوع الديكور:</div>
                <div class="info-value">{{ $order->decoration->name ?? '-' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">اسم العميل:</div>
                <div class="info-value">{{ $order->customer_name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">رقم الهاتف:</div>
                <div class="info-value">{{ $order->customer_phone }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">تاريخ الحدث:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($order->event_date)->format('Y-m-d') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">السعر الإجمالي:</div>
                <div class="info-value">
                    <strong>{{ number_format($order->total_price, 2) }} 
                    {{ $order->currency === 'dollar' ? 'دولار' : 'دينار' }}</strong>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">المبلغ المدفوع:</div>
                <div class="info-value">
                    <strong style="color: #28a745;">{{ number_format($order->paid_amount, 2) }} 
                    {{ $order->currency === 'dollar' ? 'دولار' : 'دينار' }}</strong>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">المتبقي:</div>
                <div class="info-value">
                    <strong style="color: #dc3545;">{{ number_format($order->total_price - $order->paid_amount, 2) }} 
                    {{ $order->currency === 'dollar' ? 'دولار' : 'دينار' }}</strong>
                </div>
            </div>
        </div>
        @endif

        <!-- Description -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">الوصف:</div>
                <div class="info-value">{{ $payment->description }}</div>
            </div>
        </div>

        <!-- Notes -->
        @if(isset($details['notes']) && $details['notes'])
        <div class="notes">
            <strong>ملاحظات:</strong><br>
            {{ $details['notes'] }}
        </div>
        @endif

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">توقيع المستلم</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">توقيع المحاسب</div>
            </div>
        </div>

        <!-- QR Code for Verification -->
        <div style="text-align: center; margin: 30px 0;">
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; display: inline-block;">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(route('decoration.payments.receipt', $payment->id)) }}" 
                     alt="QR Code" 
                     style="width: 150px; height: 150px;">
                <div style="font-size: 12px; color: #666; margin-top: 10px;">
                    امسح رمز QR للتحقق من الوصل
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="color: #28a745; font-weight: bold; font-size: 16px;">شكراً لتعاملكم معنا</p>
            <p style="color: #666; font-size: 14px; margin-top: 10px;">
                هذا الوصل صادر من نظام إدارة الديكورات الإلكتروني
            </p>
            <div class="print-date">
                تاريخ الطباعة: {{ now()->format('Y-m-d h:i A') }}
            </div>
        </div>
    </div>

    <script>
        // Auto print on load
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>

