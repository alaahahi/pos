<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة رقم #{{ $order->id }}</title>

    <link rel="shortcut icon" href="{{ asset('dashboard-assets/img/favicon.ico') }}"/>
    <link rel="stylesheet" href="{{ asset('/css/backend-plugin.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard-assets/css/backend.css?v=1.0.0') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/hung1001/font-awesome-pro@4cac1a6/css/all.css">
    <link rel="stylesheet" href="{{ asset('assets/vendor/remixicon/fonts/remixicon.css') }}">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            direction: rtl;
            text-align: right;
        }
        .logo-line {
            height: 4px;
            background-color: #6bcce1;
            margin-bottom: 20px;
        }
        .logo {
            width: 250px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
    </style>
</head>
<body>

<div class="wrapper container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <div class="card card-block">
                <div class="card-header bg-primary text-white d-flex justify-content-between">
                    <h4 class="mb-0">فاتورة رقم #{{ $order->id }}</h4>
                    <span>{{ $order->created_at->format('d-m-Y') }}</span>
                </div>

                <div class="card-body">
                    <!-- Logo and Customer -->
                    <div class="row mb-4">
                        <div class="col-sm-12 text-center">
                            <img src="{{ asset('dashboard-assets/img/logo.png') }}" class="logo mb-2">
                            <div class="logo-line"></div>
                        </div>
                        <div class="col-sm-12">
                            <h5 class="mb-2">مرحباً، {{ $order->customer->name }}</h5>
                            <p>
                                العنوان: {{ $order->customer->address }}<br>
                                الهاتف: {{ $order->customer->phone }}
                            </p>
                        </div>
                    </div>

                    <!-- Invoice Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>طريقة الدفع:</h6>
                            <p>{{ $order->payment_method ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6>تاريخ الاستحقاق:</h6>
                            <p>{{ $order->created_at->addDays(30)->format('d-m-Y') }}</p>
                        </div>
                    </div>

                    <!-- Products -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3">تفاصيل الطلب</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>#</th>
                                            <th>المنتج</th>
                                            <th>الكمية</th>
                                            <th>السعر</th>
                                            <th>الإجمالي</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->products as $product)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $product->name }}</td>
                                            <td>{{ $product->pivot->quantity }}</td>
                                            <td>{{env('DEFAULT_CURRENCY', 'IQD')}} {{ number_format($product->pivot->price, 2) }}</td>
                                            <td>{{env('DEFAULT_CURRENCY', 'IQD')}} {{ number_format($product->pivot->quantity * $product->pivot->price, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="row justify-content-end mt-4">
                        <div class="col-md-4">
                            <div class="border p-3 rounded">
                                <h5 class="mb-3">الإجمالي</h5>
                                <p><strong>الإجمالي الفرعي:</strong> {{env('DEFAULT_CURRENCY', 'IQD')}} {{ number_format($order->total_paid, 2) }}</p>
                                <p><strong>المبلغ الكلي:</strong> <span class="text-primary h5">{{env('DEFAULT_CURRENCY', 'IQD')}} {{ number_format($order->total_paid, 2) }}</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="row mt-3">
                        <div class="col-sm-12">
                            <p class="text-danger font-weight-bold">ملاحظات:</p>
                            <p>هذه الفاتورة تعتبر دليلاً على الشراء. لأي استفسارات، يرجى التواصل مع الدعم الفني.</p>
                        </div>
                    </div>

                    <!-- Print -->
                    <div class="row mt-4">
                        <div class="col-12 text-center">
                            <button onclick="window.print()" class="btn btn-primary">
                                <i class="fas fa-print"></i> طباعة الفاتورة
                            </button>
                        </div>
                    </div>

                </div> <!-- End card-body -->
            </div>
        </div>
    </div>
</div>

</body>
</html>
