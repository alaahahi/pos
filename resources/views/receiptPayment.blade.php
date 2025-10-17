<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وصل قبض - #{{ $transaction->id ?? 'N/A' }}</title>
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
            @if(isset($config->logo) && $config->logo)
                <img src="{{ asset('storage/' . $config->logo) }}" alt="Logo" class="company-logo">
            @endif
            <div class="company-name">{{ $config->company_name ?? 'نظام نقاط البيع' }}</div>
            <div class="company-info">
                @if(isset($config->phone) && $config->phone)
                    <div>📞 {{ $config->phone }}</div>
                @endif
                @if(isset($config->email) && $config->email)
                    <div>✉️ {{ $config->email }}</div>
                @endif
                @if(isset($config->address) && $config->address)
                    <div>📍 {{ $config->address }}</div>
                @endif
            </div>
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">وصل قبض</div>
        <div class="receipt-number">رقم الوصل: #{{ $transaction->id ?? 'N/A' }}</div>

        <!-- Amount Box -->
        <div class="amount-box">
            <div class="amount-label">المبلغ المستلم</div>
            <div class="amount-value">
                {{ number_format($transaction->amount ?? 0, 0) }} 
                {{ ($transaction->currency ?? 'IQD') === 'USD' || ($transaction->currency ?? 'IQD') === '$' ? 'دولار' : 'دينار' }}
            </div>
        </div>

        <!-- Transaction Information -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">رقم المعاملة:</div>
                <div class="info-value">#{{ $transaction->id ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">التاريخ والوقت:</div>
                <div class="info-value">
                    @if(isset($transaction->created_at))
                        {{ \Carbon\Carbon::parse($transaction->created_at)->format('Y-m-d h:i A') }}
                    @else
                        {{ now()->format('Y-m-d h:i A') }}
                    @endif
                </div>
            </div>
            @if(isset($transaction->morphed) && $transaction->morphed)
            <div class="info-row">
                <div class="info-label">اسم الحساب:</div>
                <div class="info-value"><strong>{{ $transaction->morphed->name ?? 'غير محدد' }}</strong></div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">العملة:</div>
                <div class="info-value">
                    <strong>
                        {{ ($transaction->currency ?? 'IQD') === 'USD' || ($transaction->currency ?? 'IQD') === '$' ? 'دولار أمريكي (USD)' : 'دينار عراقي (IQD)' }}
                    </strong>
                </div>
            </div>
            <div class="info-row">
                <div class="info-label">نوع المعاملة:</div>
                <div class="info-value">
                    <strong style="color: #28a745;">إضافة للصندوق</strong>
                </div>
            </div>
        </div>

        <!-- Description -->
        @if(isset($transaction->description) && $transaction->description)
        <div class="info-section">
            <div class="info-row">
                <div class="info-label">البيان:</div>
                <div class="info-value">{{ $transaction->description }}</div>
            </div>
        </div>
        @endif

        <!-- Balance Info -->
        @if(isset($clientData['user']))
        <div class="notes">
            <strong>معلومات الرصيد:</strong><br>
            <div style="margin-top: 10px;">
                <span style="display: inline-block; margin-left: 20px;">
                    <strong>رصيد الدولار:</strong> {{ number_format($clientData['user']->wallet->balance_usd ?? 0, 0) }} USD
                </span>
                <span style="display: inline-block;">
                    <strong>رصيد الدينار:</strong> {{ number_format($clientData['user']->wallet->balance ?? 0, 0) }} IQD
                </span>
            </div>
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

        <!-- Footer -->
        <div class="footer">
            <p style="color: #28a745; font-weight: bold; font-size: 16px;">شكراً لتعاملكم معنا</p>
            <p style="color: #666; font-size: 14px; margin-top: 10px;">
                هذا الوصل صادر من نظام إدارة الصندوق الإلكتروني
            </p>
            <div class="print-date">
                تاريخ الطباعة: {{ now()->format('Y-m-d h:i A') }}
            </div>
        </div>
    </div>

    <script>
        // Auto print on load
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>

