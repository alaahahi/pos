@php
    $invoiceLang = strtolower(env('INVOICE_LANGUAGE', 'en'));
    $isArabic = ($invoiceLang === 'ar');
    $dir = $isArabic ? 'rtl' : 'ltr';
    $lang = $isArabic ? 'ar' : 'en';
    $fontFamily = $isArabic ? "'Cairo', 'Arial', sans-serif" : "'Arial', 'Helvetica', sans-serif";
    
    $months = [
        1 => $isArabic ? 'يناير' : 'January',
        2 => $isArabic ? 'فبراير' : 'February',
        3 => $isArabic ? 'مارس' : 'March',
        4 => $isArabic ? 'أبريل' : 'April',
        5 => $isArabic ? 'مايو' : 'May',
        6 => $isArabic ? 'يونيو' : 'June',
        7 => $isArabic ? 'يوليو' : 'July',
        8 => $isArabic ? 'أغسطس' : 'August',
        9 => $isArabic ? 'سبتمبر' : 'September',
        10 => $isArabic ? 'أكتوبر' : 'October',
        11 => $isArabic ? 'نوفمبر' : 'November',
        12 => $isArabic ? 'ديسمبر' : 'December',
    ];
    
    $texts = [
        'title' => $isArabic ? 'تقرير الإغلاق الشهري' : 'MONTHLY CLOSES REPORT',
        'from_date' => $isArabic ? 'من:' : 'FROM:',
        'to_date' => $isArabic ? 'إلى:' : 'TO:',
        'no' => $isArabic ? 'رقم' : 'NO',
        'month' => $isArabic ? 'الشهر' : 'MONTH',
        'year' => $isArabic ? 'السنة' : 'YEAR',
        'status' => $isArabic ? 'الحالة' : 'STATUS',
        'sales_usd' => $isArabic ? 'المبيعات (USD)' : 'SALES (USD)',
        'sales_iqd' => $isArabic ? 'المبيعات (IQD)' : 'SALES (IQD)',
        'direct_deposits_usd' => $isArabic ? 'الإضافة المباشرة (USD)' : 'DIRECT DEPOSITS (USD)',
        'direct_deposits_iqd' => $isArabic ? 'الإضافة المباشرة (IQD)' : 'DIRECT DEPOSITS (IQD)',
        'direct_withdrawals_usd' => $isArabic ? 'السحب المباشر (USD)' : 'DIRECT WITHDRAWALS (USD)',
        'direct_withdrawals_iqd' => $isArabic ? 'السحب المباشر (IQD)' : 'DIRECT WITHDRAWALS (IQD)',
        'expenses_usd' => $isArabic ? 'المصاريف (USD)' : 'EXPENSES (USD)',
        'expenses_iqd' => $isArabic ? 'المصاريف (IQD)' : 'EXPENSES (IQD)',
        'total_orders' => $isArabic ? 'عدد الطلبات' : 'TOTAL ORDERS',
        'total_sales_usd' => $isArabic ? 'إجمالي المبيعات (USD):' : 'TOTAL SALES (USD):',
        'total_sales_iqd' => $isArabic ? 'إجمالي المبيعات (IQD):' : 'TOTAL SALES (IQD):',
        'total_direct_deposits_usd' => $isArabic ? 'إجمالي الإضافة المباشرة (USD):' : 'TOTAL DIRECT DEPOSITS (USD):',
        'total_direct_deposits_iqd' => $isArabic ? 'إجمالي الإضافة المباشرة (IQD):' : 'TOTAL DIRECT DEPOSITS (IQD):',
        'total_direct_withdrawals_usd' => $isArabic ? 'إجمالي السحب المباشر (USD):' : 'TOTAL DIRECT WITHDRAWALS (USD):',
        'total_direct_withdrawals_iqd' => $isArabic ? 'إجمالي السحب المباشر (IQD):' : 'TOTAL DIRECT WITHDRAWALS (IQD):',
        'total_expenses_usd' => $isArabic ? 'إجمالي المصاريف (USD):' : 'TOTAL EXPENSES (USD):',
        'total_expenses_iqd' => $isArabic ? 'إجمالي المصاريف (IQD):' : 'TOTAL EXPENSES (IQD):',
        'total_orders_label' => $isArabic ? 'إجمالي الطلبات:' : 'TOTAL ORDERS:',
        'closed' => $isArabic ? 'مغلق' : 'CLOSED',
        'open' => $isArabic ? 'مفتوح' : 'OPEN',
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
            size: A4 landscape;
            margin: 0;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: {{ $fontFamily }};
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            background: #f5f5f5;
            padding: 10mm;
            direction: {{ $dir }};
        }
        
        .report-container {
            width: 100%;
            max-width: 297mm;
            margin: 0 auto;
            background: white;
            position: relative;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            min-height: 210mm;
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
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            font-size: 11px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 15px;
            font-size: 9px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .items-table th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-color-dark) 100%);
            color: #fff;
            border: none;
            padding: 6px 4px;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8px;
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
            padding: 4px;
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
                            <span class="info-value">{{ $months[$filters['start_month']] ?? $filters['start_month'] }} {{ $filters['start_year'] }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="info-row">
                            <span class="info-label">{{ $texts['to_date'] }}</span>
                            <span class="info-value">{{ $months[$filters['end_month']] ?? $filters['end_month'] }} {{ $filters['end_year'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics -->
            <div class="statistics-section">
                <div class="section-title">{{ $isArabic ? 'إحصائيات الإغلاق الشهري' : 'MONTHLY CLOSES STATISTICS' }}</div>
                <div class="stats-grid">
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_sales_usd'] }}</span>
                        <span class="info-value">{{ rtrim(rtrim(number_format($statistics['total_sales_usd'], 2), '0'), '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_sales_iqd'] }}</span>
                        <span class="info-value">{{ rtrim(rtrim(number_format($statistics['total_sales_iqd'], 2), '0'), '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_direct_deposits_usd'] }}</span>
                        <span class="info-value">{{ rtrim(rtrim(number_format($statistics['total_direct_deposits_usd'], 2), '0'), '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_direct_deposits_iqd'] }}</span>
                        <span class="info-value">{{ rtrim(rtrim(number_format($statistics['total_direct_deposits_iqd'], 2), '0'), '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_direct_withdrawals_usd'] }}</span>
                        <span class="info-value">{{ rtrim(rtrim(number_format($statistics['total_direct_withdrawals_usd'], 2), '0'), '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_direct_withdrawals_iqd'] }}</span>
                        <span class="info-value">{{ rtrim(rtrim(number_format($statistics['total_direct_withdrawals_iqd'], 2), '0'), '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_expenses_usd'] }}</span>
                        <span class="info-value">{{ rtrim(rtrim(number_format($statistics['total_expenses_usd'], 2), '0'), '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_expenses_iqd'] }}</span>
                        <span class="info-value">{{ rtrim(rtrim(number_format($statistics['total_expenses_iqd'], 2), '0'), '.') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">{{ $texts['total_orders_label'] }}</span>
                        <span class="info-value">{{ $statistics['total_orders'] }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Monthly Closes Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 3%;">{{ $texts['no'] }}</th>
                        <th style="width: 10%;">{{ $texts['month'] }}</th>
                        <th style="width: 6%;">{{ $texts['year'] }}</th>
                        <th style="width: 6%;">{{ $texts['status'] }}</th>
                        <th style="width: 8%;">{{ $texts['sales_usd'] }}</th>
                        <th style="width: 8%;">{{ $texts['sales_iqd'] }}</th>
                        <th style="width: 8%;">{{ $texts['direct_deposits_usd'] }}</th>
                        <th style="width: 8%;">{{ $texts['direct_deposits_iqd'] }}</th>
                        <th style="width: 8%;">{{ $texts['direct_withdrawals_usd'] }}</th>
                        <th style="width: 8%;">{{ $texts['direct_withdrawals_iqd'] }}</th>
                        <th style="width: 8%;">{{ $texts['expenses_usd'] }}</th>
                        <th style="width: 8%;">{{ $texts['expenses_iqd'] }}</th>
                        <th style="width: 7%;">{{ $texts['total_orders'] }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($monthly_closes as $index => $close)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $months[$close->month] ?? $close->month }}</td>
                        <td>{{ $close->year }}</td>
                        <td>{{ $close->status === 'closed' ? $texts['closed'] : $texts['open'] }}</td>
                        <td>{{ rtrim(rtrim(number_format($close->total_sales_usd, 2), '0'), '.') }}</td>
                        <td>{{ rtrim(rtrim(number_format($close->total_sales_iqd, 2), '0'), '.') }}</td>
                        <td>{{ rtrim(rtrim(number_format($close->direct_deposits_usd, 2), '0'), '.') }}</td>
                        <td>{{ rtrim(rtrim(number_format($close->direct_deposits_iqd, 2), '0'), '.') }}</td>
                        <td>{{ rtrim(rtrim(number_format($close->direct_withdrawals_usd, 2), '0'), '.') }}</td>
                        <td>{{ rtrim(rtrim(number_format($close->direct_withdrawals_iqd, 2), '0'), '.') }}</td>
                        <td>{{ rtrim(rtrim(number_format($close->total_expenses_usd, 2), '0'), '.') }}</td>
                        <td>{{ rtrim(rtrim(number_format($close->total_expenses_iqd, 2), '0'), '.') }}</td>
                        <td>{{ $close->total_orders }}</td>
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

