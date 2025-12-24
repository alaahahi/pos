<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TAX INVOICE #{{ $order->id }}</title>
    <style>
        @php
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
            font-family: 'Arial', 'Helvetica', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            background: #f5f5f5;
            padding: 10mm;
        }
        
        .invoice-container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            background: white;
            position: relative;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            min-height: 277mm;
        }
        
        /* Watermark - اللوجو كعلامة مائية */
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
        
        /* Company Logo in Header */
        .company-logo-header {
            max-width: 100px;
            max-height: 60px;
            object-fit: contain;
            flex-shrink: 0;
        }
        
        .invoice-content {
            position: relative;
            z-index: 1;
            padding: 15mm;
        }
        
        /* Header - تحسين التصميم */
        .invoice-header {
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
        
        .company-name-ar {
            font-size: 12px;
            font-weight: normal;
            color: #666;
            line-height: 1.3;
        }
        
        .document-type {
            font-size: 32px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--primary-color-dark);
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
            white-space: nowrap;
            margin-left: 20px;
        }
        
        .invoice-info-grid {
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
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: bold;
            min-width: 100px;
            color: #555;
        }
        
        .info-value {
            flex: 1;
            text-align: right;
            color: #333;
            font-weight: 500;
        }
        
        /* Customer Details - تحسين */
        .customer-section {
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
        
        .customer-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            font-size: 11px;
        }
        
        /* Items Table - تحسين كبير */
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
        }
        
        .items-table th:first-child {
            border-radius: 8px 0 0 0;
        }
        
        .items-table th:last-child {
            border-radius: 0 8px 0 0;
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
        
        .items-table tr:hover td {
            background: rgba({{ $primaryRgb }}, 0.05);
        }
        
        .items-table td.text-left {
            text-align: left;
            font-weight: 500;
        }
        
        /* Summary Section - تحسين */
        .summary-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 15px;
        }
        
        .summary-right {
            border: 2px solid var(--primary-color);
            padding: 12px;
            border-radius: 8px;
            background: linear-gradient(135deg, rgba({{ $primaryRgb }}, 0.05) 0%, white 100%);
            width: 100%;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 12px;
            padding: 5px 0;
            border-bottom: 1px dotted #ddd;
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .summary-label {
            font-weight: bold;
            color: #555;
        }
        
        .summary-total {
            font-weight: bold;
            font-size: 16px;
            border-top: 3px solid var(--primary-color);
            padding-top: 8px;
            margin-top: 6px;
            color: var(--primary-color-dark);
        }
        
        .summary-total span:last-child {
            font-size: 20px;
            color: var(--primary-color-dark);
        }
        
        .amount-words-row {
            border-top: 2px solid var(--primary-color);
            padding-top: 8px;
            margin-top: 6px;
            margin-bottom: 0 !important;
        }
        
        .amount-words-row span:last-child {
            text-align: right;
            font-size: 12px;
        }
        
        /* Footer - تحسين كبير */
        .invoice-footer {
            border-top: 3px solid var(--primary-color);
            padding-top: 15px;
            margin-top: 20px;
            background: linear-gradient(135deg, rgba({{ $primaryRgb }}, 0.03) 0%, white 100%);
            padding: 15px 20px;
            border-radius: 0 0 8px 8px;
        }
        
        .footer-terms {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 12px;
            font-size: 11px;
        }
        
        .dealer-info {
            text-align: center;
            font-size: 11px;
            margin: 10px 0;
            font-style: italic;
            color: #666;
            padding: 8px;
            background: rgba({{ $primaryRgb }}, 0.05);
            border-radius: 5px;
        }
        
        /* Brand Logos - توزيع 5 براندات على عرض الصفحة */
        .brand-logos {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 15px 0;
            padding: 10px 10px;
            width: 100%;
        }
        
        .brand-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            text-align: center;
            padding: 0 5px;
        }
        
        .brand-logo-img {
            width: 100%;
            max-width: 120px;
            max-height: 60px;
            object-fit: contain;
            display: block;
        }
        
        .brand-logo-icon,
        .brand-logo-text {
            display: none !important;
        }
        
        .contact-info {
            text-align: center;
            font-size: 11px;
            margin-top: 12px;
            line-height: 1.6;
            color: #555;
            padding: 10px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        
        .contact-info strong {
            color: var(--primary-color-dark);
            margin-right: 5px;
        }
        
        .page-info {
            text-align: center;
            font-size: 10px;
            margin-top: 10px;
            color: #999;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }
        
        /* Print Styles */
        @media print {
            body {
                padding: 0;
                background: white;
            }
            
            .no-print {
                display: none;
            }
            
            .invoice-container {
                box-shadow: none;
            }
            
            .watermark {
                position: absolute !important;
                opacity: 0.1 !important;
            }
            
            .company-logo-header {
                max-width: 100px;
                max-height: 60px;
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
        
        /* Colors from .env */
        :root {
            --primary-color: {{ $primaryColor }};
            --primary-color-dark: {{ $primaryColorDark }};
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Print Invoice</button>
    
    <div class="invoice-container">
        @if(env('COMPANY_LOGO'))
        <div class="watermark"></div>
        @endif
        
        <div class="invoice-content">
            <!-- Header -->
            <div class="invoice-header">
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
                            @if(env('COMPANY_BRANCH'))
                            <div class="company-name-ar">{{ env('COMPANY_BRANCH') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="document-type">TAX INVOICE</div>
                </div>
                
                <div class="invoice-info-grid">
                    <div>
                        <div class="info-row">
                            <span class="info-label">INV NO:</span>
                            <span class="info-value">{{ $order->id }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">INV DATE:</span>
                            <span class="info-value">{{ $order->date ? \Carbon\Carbon::parse($order->date)->format('d/m/Y') : $order->created_at->format('d/m/Y') }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="info-row">
                            <span class="info-label">DO NO:</span>
                            <span class="info-value">{{ $order->id }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">SALESMAN:</span>
                            <span class="info-value">{{ $order->salesman ?? auth()->user()->name ?? '' }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Customer Details -->
            <div class="customer-section">
                <div class="section-title">CUSTOMER DETAILS</div>
                <div class="customer-grid">
                    <div class="info-row">
                        <span class="info-label">CUSTOMER:</span>
                        <span class="info-value">{{ $order->customer->name ?? 'CASH' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">TEL:</span>
                        <span class="info-value">{{ $order->customer->phone ?? '' }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">NO</th>
                        <th style="width: 45%;" class="text-left">DESCRIPTION</th>
                        <th style="width: 10%;">QTY</th>
                        <th style="width: 15%;">UNIT PRICE</th>
                        <th style="width: 25%;">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $subtotal = 0;
                        $discountAmount = $order->discount_amount ?? 0;
                        $discountRate = $order->discount_rate ?? 0;
                    @endphp
                    @foreach($order->products as $index => $product)
                    @php
                        $quantity = $product->pivot->quantity ?? 1;
                        $unitPrice = $product->pivot->price ?? 0;
                        $itemTotal = $quantity * $unitPrice;
                        
                        $subtotal += $itemTotal;
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="text-left">{{ $product->name }}</td>
                        <td>{{ number_format($quantity, 0) }}</td>
                        <td>{{ number_format($unitPrice, 2) }}</td>
                        <td>{{ number_format($itemTotal, 2) }}</td>
                    </tr>
                    @endforeach
                    @php
                        $grandTotal = $subtotal - $discountAmount;
                    @endphp
                </tbody>
            </table>
            
            <!-- Summary -->
            <div class="summary-section">
                <div class="summary-right">
                    <div class="summary-row">
                        <span class="summary-label">SUB TOTAL:</span>
                        <span>{{ number_format($subtotal, 2) }}</span>
                    </div>
                    @if($discountAmount > 0)
                    <div class="summary-row">
                        <span class="summary-label">DISCOUNT{{ $discountRate > 0 ? ' (' . number_format($discountRate, 2) . '%)' : '' }}:</span>
                        <span style="color: #dc3545; font-weight: bold;">- {{ number_format($discountAmount, 2) }}</span>
                    </div>
                    @endif
                    <div class="summary-row summary-total">
                        <span>TOTAL:</span>
                        <span>{{ number_format($grandTotal, 2) }}</span>
                    </div>
                    <div class="summary-row amount-words-row">
                        <span class="summary-label">AMOUNT IN WORDS:</span>
                        <span style="font-weight: 500;">{{ \App\Helpers\NumberToWords::convert($grandTotal) }} ONLY</span>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="invoice-footer">
                <div class="footer-terms">
                    <div>
                        <div class="info-row">
                            <span class="info-label">TERMS:</span>
                            <span class="info-value">{{ $order->terms ?? '' }}</span>
                        </div>
                    </div>
                    <div>
                        <div class="info-row">
                            <span class="info-label">RECEIVED BY:</span>
                            <span class="info-value">{{ $order->received_by ?? '' }}</span>
                        </div>
                    </div>
                </div>
                
                @if(env('COMPANY_DEALER'))
                <div class="dealer-info">Authorised Dealer of: {{ env('COMPANY_DEALER') }}</div>
                @endif
                
                @php
                    $companyBrands = env('COMPANY_BRANDS', 'TOYOTA,LEXUS,NISSAN,INFINITI,RENAULT');
                    $brands = explode(',', $companyBrands);
                    $brandLogosPath = env('COMPANY_BRANDS_LOGOS_PATH', 'dashboard-assets/img/brands/');
                    $brandIcons = [
                        'TOYOTA' => '🚗',
                        'LEXUS' => '🚙',
                        'NISSAN' => '🚘',
                        'INFINITI' => '🚗',
                        'RENAULT' => '🚙',
                        'HONDA' => '🚗',
                        'BMW' => '🚗',
                        'MERCEDES' => '🚙',
                        'AUDI' => '🚗',
                        'VOLKSWAGEN' => '🚙',
                    ];
                @endphp
                @if(!empty($brands) && count($brands) > 0)
                <div class="brand-logos">
                    @foreach($brands as $index => $brand)
                    @php
                        $brandName = trim($brand);
                        if(empty($brandName)) continue;
                        $brandNameUpper = strtoupper($brandName);
                        $brandNameLower = strtolower(str_replace(' ', '-', $brandName));
                        $logoPath = $brandLogosPath . $brandNameLower . '.png';
                        $brandIcon = $brandIcons[$brandNameUpper] ?? '🚗';
                    @endphp
                    <div class="brand-item">
                        <img src="{{ asset($logoPath) }}" alt="{{ $brandName }}" class="brand-logo-img" onerror="this.style.display='none';">
                    </div>
                    @endforeach
                </div>
                @endif
                
                <div class="contact-info">
                    @if(env('COMPANY_PHONE'))
                    <strong>Tel:</strong> {{ env('COMPANY_PHONE') }}
                    @endif
                    @if(env('COMPANY_ADDRESS'))
                    <br><strong>Address:</strong> {{ env('COMPANY_ADDRESS') }}
                    @endif
                    @if(env('COMPANY_EMAIL'))
                    <br><strong>Email:</strong> {{ env('COMPANY_EMAIL') }}
                    @endif
                    @if(env('COMPANY_WEBSITE'))
                    <br><strong>Web:</strong> {{ env('COMPANY_WEBSITE') }}
                    @endif
                </div>
                
                <div class="page-info">
                    Page 1 of 1 | Print Time: {{ now()->format('g:i:s A') }} | {{ env('COMPANY_WEBSITE', 'http://intellij-app.com/') }}
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>
