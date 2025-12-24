@php
    $invoiceLang = strtolower(env('INVOICE_LANGUAGE', 'en'));
    $isArabic = ($invoiceLang === 'ar');
    $dir = $isArabic ? 'rtl' : 'ltr';
    $lang = $isArabic ? 'ar' : 'en';
    $fontFamily = $isArabic ? "'Cairo', 'Arial', sans-serif" : "'Arial', 'Helvetica', sans-serif";
    
    $texts = [
        'title' => $isArabic ? 'تقرير المبيعات' : 'SALES REPORT',
        'from_date' => $isArabic ? 'من تاريخ:' : 'FROM DATE:',
        'to_date' => $isArabic ? 'إلى تاريخ:' : 'TO DATE:',
        'no' => $isArabic ? 'رقم' : 'NO',
        'date' => $isArabic ? 'التاريخ' : 'DATE',
        'customer' => $isArabic ? 'العميل' : 'CUSTOMER',
        'amount' => $isArabic ? 'المبلغ' : 'AMOUNT',
        'paid' => $isArabic ? 'المدفوع' : 'PAID',
        'status' => $isArabic ? 'الحالة' : 'STATUS',
        'total_sales' => $isArabic ? 'إجمالي المبيعات:' : 'TOTAL SALES:',
        'total_paid' => $isArabic ? 'إجمالي المدفوع:' : 'TOTAL PAID:',
        'total_remaining' => $isArabic ? 'المتبقي:' : 'REMAINING:',
        'total_count' => $isArabic ? 'عدد الفواتير:' : 'TOTAL INVOICES:',
        'print_time' => $isArabic ? 'وقت الطباعة:' : 'Print Time:',
    ];
    
    function hexToRgb($hex) {
        $hex = str_replace('#', '', $hex);
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "$r, $g, $b";
    }
    $primaryColor = env('PRIMARY_COLOR', '#6bcce1');
    $primaryColorDark = env('PRIMARY_COLOR_DARK', '#0056b3');
    $primaryRgb = hexToRgb($primaryColor);
@endphp
<!DOCTYPE html>
<html lang="{{ $lang }}" dir="{{ $dir }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @if($isArabic)
    <link href='https://fonts.googleapis.com/css?family=Cairo' rel='stylesheet'>
    @endif
    <title>{{ $texts['title'] }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: {{ $fontFamily }};
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #f5f5f5;
            padding: 10mm;
            direction: {{ $dir }};
        }
        
        .report-container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            position: relative;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            min-height: 277mm;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            height: 500px;
            max-width: 80%;
            max-height: 80%;
            opacity: 0.12;
            background-image: url('{{ asset(env("COMPANY_LOGO", "dashboard-assets/img/logo.png")) }}');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            z-index: 0;
            pointer-events: none;
        }
        
        .company-logo-header {
            max-width: 100px;
            max-height: 60px;
            object-fit: contain;
            flex-shrink: 0;
        }
        
        .report-content {
            position: relative;
            z-index: 1;
            padding: 15mm;
        }
        
        .report-header {
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, rgba({{ $primaryRgb }}, 0.05) 0%, rgba({{ $primaryRgb }}, 0.02) 100%);
            padding: 12px;
            border-radius: 8px 8px 0 0;
        }
        
        .header-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .company-info-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .company-name-en {
            font-size: 20px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
            color: var(--primary-color-dark);
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        
        .document-type {
            font-size: 32px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--primary-color-dark);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            white-space: nowrap;
            {{ $isArabic ? 'margin-right: 20px;' : 'margin-left: 20px;' }}
        }
        
        .report-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 8px;
            font-size: 11px;
            background: white;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #e0e0e0;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 3px 0;
            border-bottom: 1px dotted #ddd;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 100px;
            color: #555;
        }
        
        .info-value {
            flex: 1;
            text-align: {{ $isArabic ? 'left' : 'right' }};
            color: #333;
            font-weight: 500;
        }
        
        .statistics-section {
            border: 2px solid var(--primary-color);
            padding: 12px;
            margin-bottom: 15px;
            background: linear-gradient(135deg, rgba({{ $primaryRgb }}, 0.05) 0%, white 100%);
            border-radius: 8px;
        }
        
        .section-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 8px;
            text-transform: uppercase;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 5px;
            color: var(--primary-color-dark);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            font-size: 11px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 15px;
            font-size: 11px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .items-table th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
            color: #fff;
            border: none;
            padding: 8px 8px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            letter-spacing: 0.5px;
            position: relative;
        }
        
        .items-table th:first-child {
            border-radius: 8px 0 0 0;
            border-top: 2px solid var(--primary-color-dark);
            border-left: 2px solid var(--primary-color-dark);
        }
        
        .items-table th:last-child {
            border-radius: 0 8px 0 0;
            border-top: 2px solid var(--primary-color-dark);
            border-right: 2px solid var(--primary-color-dark);
        }
        
        .items-table th:not(:first-child):not(:last-child) {
            border-top: 2px solid var(--primary-color-dark);
        }
        
        .items-table td {
            border: 1px solid #e8e8e8;
            padding: 6px 8px;
            text-align: center;
            background: white;
        }
        
        .items-table tr:nth-child(even) td {
            background: #fafafa;
        }
        
        .report-footer {
            border-top: 3px solid var(--primary-color);
            padding-top: 15px;
            margin-top: 20px;
            background: linear-gradient(135deg, rgba({{ $primaryRgb }}, 0.03) 0%, white 100%);
            padding: 15px 20px;
            border-radius: 0 0 8px 8px;
        }
        
        .page-info {
            text-align: center;
            font-size: 10px;
            margin-top: 10px;
            color: #999;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
            }
            
            .no-print {
                display: none;
            }
            
            .report-container {
                box-shadow: none;
            }
            
            .watermark {
                position: absolute !important;
                opacity: 0.1 !important;
            }
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .print-button:hover {
            background: var(--primary-color-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.3);
        }
        
        :root {
            --primary-color: {{ $primaryColor }};
            --primary-color-dark: {{ $primaryColorDark }};
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ {{ $isArabic ? 'طباعة التقرير' : 'Print Report' }}</button>
    
    <div class="report-container">
        @if(env('COMPANY_LOGO'))
        <div class="watermark"></div>
        @endif
        
        <div class="report-content">
            <!-- Header -->
            <div class="report-header">
                <div class="header-top-row">
                    <div class="company-info-left">
                        @if(env('COMPANY_LOGO'))
                        <img src="{{ asset(env('COMPANY_LOGO', 'dashboard-assets/img/logo.png')) }}" alt="Company Logo" class="company-logo-header">
                        @endif
                        <div>
                            <div class="company-name-en">{{ env('COMPANY_NAME', 'COMPANY NAME') }}</div>
                            @if(env('COMPANY_NAME_AR'))
                            <div class="company-name-ar">{{ env('COMPANY_NAME_AR') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="document-type">{{ $texts['title'] }}</div>
                </div>
                
                <div class="report-info-grid">
                    <div>
                        <div class="info-row">
                            <span class="info-label">{{ $texts['from_date'] }}</span>
                            <span class="info-value">{{ \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="info-row">
                            <span class="info-label">{{ $texts['to_date'] }}</span>
                            <span class="info-value">{{ \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="statistics-section">
                <div class="section-title">{{ $isArabic ? 'إحصائيات المبيعات' : 'SALES STATISTICS' }}</div>
                <div class="stats-grid">
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_sales'] }}</span>
                        <span class="info-value">{{ rtrim(rtrim(number_format($statistics['total_amount'], 2), '0'), '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_paid'] }}</span>
                        <span class="info-value">{{ rtrim(rtrim(number_format($statistics['total_paid'], 2), '0'), '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_remaining'] }}</span>
                        <span class="info-value">{{ rtrim(rtrim(number_format($statistics['remaining'], 2), '0'), '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_count'] }}</span>
                        <span class="info-value">{{ $statistics['total_count'] }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Orders Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">{{ $texts['no'] }}</th>
                        <th style="width: 15%;">{{ $texts['date'] }}</th>
                        <th style="width: 30%;" class="text-left">{{ $texts['customer'] }}</th>
                        <th style="width: 20%;">{{ $texts['amount'] }}</th>
                        <th style="width: 20%;">{{ $texts['paid'] }}</th>
                        <th style="width: 10%;">{{ $texts['status'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $index => $order)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}</td>
                        <td class="text-left">{{ $order->customer->name ?? 'N/A' }}</td>
                        <td>{{ rtrim(rtrim(number_format($order->final_amount ?? $order->total_amount, 2), '0'), '.') }}</td>
                        <td>{{ rtrim(rtrim(number_format($order->total_paid, 2), '0'), '.') }}</td>
                        <td>{{ $order->status ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <!-- Footer -->
            <div class="report-footer">
                <div class="page-info">
                    {{ $texts['print_time'] }} {{ now()->format('g:i:s A') }} | {{ env('COMPANY_WEBSITE', 'http://intellij-app.com/') }}
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>

