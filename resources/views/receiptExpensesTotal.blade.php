<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف حساب - إجمالي المصروفات</title>
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

        .report-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            border-radius: 10px;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #dc3545;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .company-name {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .report-title {
            text-align: center;
            font-size: 28px;
            font-weight: bold;
            color: #dc3545;
            margin: 30px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }

        th {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: #f8f9fa;
        }

        tr:hover {
            background: #e9ecef;
        }

        .total-row {
            background: #dc3545 !important;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }

        .summary-box {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin: 30px 0;
        }

        .summary-value {
            font-size: 42px;
            font-weight: bold;
            margin-top: 10px;
        }

        @media print {
            body {
                padding: 0;
                background: white;
            }
            
            .report-container {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="report-container">
        <div class="header">
            <div class="company-name">{{ $config->company_name ?? 'نظام نقاط البيع' }}</div>
        </div>

        <div class="report-title">كشف حساب - إجمالي المصروفات</div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>التاريخ</th>
                    <th>الحساب</th>
                    <th>البيان</th>
                    <th>المبلغ</th>
                    <th>العملة</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalUSD = 0;
                    $totalIQD = 0;
                    $count = 1;
                @endphp
                @if(isset($clientData['transactions']) && count($clientData['transactions']) > 0)
                    @foreach($clientData['transactions'] as $trans)
                        @if(in_array($trans->type, ['out', 'outUser']))
                        <tr>
                            <td>{{ $count++ }}</td>
                            <td>{{ \Carbon\Carbon::parse($trans->created_at)->format('Y-m-d') }}</td>
                            <td>{{ $trans->morphed->name ?? 'غير محدد' }}</td>
                            <td>{{ $trans->description ?? '-' }}</td>
                            <td>{{ number_format($trans->amount, 0) }}</td>
                            <td>{{ $trans->currency === 'USD' || $trans->currency === '$' ? 'دولار' : 'دينار' }}</td>
                        </tr>
                        @php
                            if($trans->currency === 'USD' || $trans->currency === '$') {
                                $totalUSD += $trans->amount;
                            } else {
                                $totalIQD += $trans->amount;
                            }
                        @endphp
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td colspan="6">لا توجد معاملات</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td colspan="4">الإجمالي</td>
                    <td colspan="2">
                        {{ number_format($totalUSD, 0) }} USD<br>
                        {{ number_format($totalIQD, 0) }} IQD
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="text-align: center; margin-top: 30px; color: #666;">
            تاريخ الطباعة: {{ now()->format('Y-m-d h:i A') }}
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
    </script>
</body>
</html>

