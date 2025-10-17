<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير حركة الصندوق</title>
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
            border-bottom: 3px solid #667eea;
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
            color: #667eea;
            margin: 30px 0;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }

        .summary-card {
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            color: white;
        }

        .summary-card.in {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .summary-card.out {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }

        .summary-card.balance {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .summary-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .summary-card .value {
            font-size: 32px;
            font-weight: bold;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: #f8f9fa;
        }

        tr.in-row {
            background: rgba(40, 167, 69, 0.1);
        }

        tr.out-row {
            background: rgba(220, 53, 69, 0.1);
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

        <div class="report-title">تقرير حركة الصندوق</div>

        @php
            $totalInUSD = 0;
            $totalInIQD = 0;
            $totalOutUSD = 0;
            $totalOutIQD = 0;
        @endphp

        @if(isset($data['transactions']))
            @foreach($data['transactions'] as $trans)
                @if(in_array($trans->type, ['in', 'inUser']))
                    @php
                        if($trans->currency === 'USD' || $trans->currency === '$') {
                            $totalInUSD += $trans->amount;
                        } else {
                            $totalInIQD += $trans->amount;
                        }
                    @endphp
                @elseif(in_array($trans->type, ['out', 'outUser']))
                    @php
                        if($trans->currency === 'USD' || $trans->currency === '$') {
                            $totalOutUSD += $trans->amount;
                        } else {
                            $totalOutIQD += $trans->amount;
                        }
                    @endphp
                @endif
            @endforeach
        @endif

        <div class="summary-cards">
            <div class="summary-card in">
                <h3>إجمالي الإضافات</h3>
                <div class="value">{{ number_format($totalInUSD, 0) }} $</div>
                <div class="value" style="font-size: 20px; margin-top: 5px;">{{ number_format($totalInIQD, 0) }} IQD</div>
            </div>
            <div class="summary-card out">
                <h3>إجمالي السحوبات</h3>
                <div class="value">{{ number_format($totalOutUSD, 0) }} $</div>
                <div class="value" style="font-size: 20px; margin-top: 5px;">{{ number_format($totalOutIQD, 0) }} IQD</div>
            </div>
            <div class="summary-card balance">
                <h3>الرصيد النهائي</h3>
                <div class="value">{{ number_format($totalInUSD - $totalOutUSD, 0) }} $</div>
                <div class="value" style="font-size: 20px; margin-top: 5px;">{{ number_format($totalInIQD - $totalOutIQD, 0) }} IQD</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>التاريخ</th>
                    <th>النوع</th>
                    <th>الحساب</th>
                    <th>البيان</th>
                    <th>المبلغ</th>
                    <th>العملة</th>
                </tr>
            </thead>
            <tbody>
                @php $count = 1; @endphp
                @if(isset($data['transactions']) && count($data['transactions']) > 0)
                    @foreach($data['transactions'] as $trans)
                    <tr class="{{ in_array($trans->type, ['in', 'inUser']) ? 'in-row' : 'out-row' }}">
                        <td>{{ $count++ }}</td>
                        <td>{{ \Carbon\Carbon::parse($trans->created_at)->format('Y-m-d h:i A') }}</td>
                        <td>
                            @if(in_array($trans->type, ['in', 'inUser']))
                                <span style="color: #28a745; font-weight: bold;">إضافة</span>
                            @else
                                <span style="color: #dc3545; font-weight: bold;">سحب</span>
                            @endif
                        </td>
                        <td>{{ $trans->morphed->name ?? 'غير محدد' }}</td>
                        <td>{{ $trans->description ?? '-' }}</td>
                        <td style="font-weight: bold;">{{ number_format($trans->amount, 0) }}</td>
                        <td>{{ $trans->currency === 'USD' || $trans->currency === '$' ? 'دولار' : 'دينار' }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7">لا توجد معاملات</td>
                    </tr>
                @endif
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

