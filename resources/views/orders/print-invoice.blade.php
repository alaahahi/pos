<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>POS Dash - Invoice #{{ $order->id }}</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ asset('dashboard-assets/img/favicon.ico') }}"/>
    <link rel="stylesheet" href="{{ asset('/css/backend-plugin.min.css') }}">
    <link rel="stylesheet" href="{{ asset('dashboard-assets/css/backend.css?v=1.0.0') }}">
    <link href="https://cdn.jsdelivr.net/gh/hung1001/font-awesome-pro@4cac1a6/css/all.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/vendor/remixicon/fonts/remixicon.css') }}">
</head>
<body>

<!-- Wrapper Start -->
<div class="wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <div class="card card-block">
                    <div class="card-header d-flex justify-content-between bg-primary">
                        <div class="iq-header-title">
                            <h4 class="card-title mb-0">Invoice #{{ $order->id }}</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-12">
                                <img src="{{ asset('dashboard-assets/img/logo.png') }}" width="200px" class="  img-fluid mb-3">
                                <h5 class="mb-3">Hello, {{ $order->customer->name }}</h5>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="table-responsive-sm">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th scope="col">Order Date</th>
                                                <th scope="col">Order Status</th>
                                                <th scope="col">Invoice No</th>
                                                <th scope="col">Billing Address</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>{{ $order->created_at->format('d-m-Y') }}</td>
                                                <td><span class="badge badge-success">Paid</span></td>
                                                <td>{{ $order->id }}</td>
                                                <td>
                                                    <p class="mb-0">
                                                        {{ $order->customer->address }}<br>
                                                        Name: {{ $order->customer->name ?? '-' }}<br>
                                                        Phone: {{ $order->customer->phone }}<br>
                                                    </p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="row">
                            <div class="col-sm-12">
                                <h5 class="mb-3">Order Summary</h5>
                                <div class="table-responsive-lg">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th class="text-center">#</th>
                                                <th>Item</th>
                                                <th class="text-center">Quantity</th>
                                                <th class="text-center">Price</th>
                                                <th class="text-center">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($order->products as $product)
                                            <tr>
                                                <th class="text-center">{{ $loop->iteration }}</th>
                                                <td><h6 class="mb-0">{{ $product->name }}</h6></td>
                                                <td class="text-center">{{ $product->pivot->quantity }}</td>
                                                <td class="text-center">${{ number_format($product->pivot->price, 2) }}</td>
                                                <td class="text-center"><b>${{ number_format($product->pivot->quantity * $product->pivot->price, 2) }}</b></td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="row">
                            <div class="col-sm-12">
                                <b class="text-danger">Notes:</b>
                                <p class="mb-0">This invoice serves as proof of purchase. If you have any questions, please contact our support.</p>
                            </div>
                        </div>

                        <!-- Order Details -->
                        <div class="row mt-4 mb-3">
                            <div class="offset-lg-8 col-lg-4">
                                <div class="or-detail rounded">
                                    <div class="p-3">
                                        <h5 class="mb-3">Order Details</h5>
                                        <div class="mb-2">
                                            <h6>Payment method</h6>
                                            <p>{{ $order->payment_method ?? '-' }}</p>
                                        </div>
                                        <div class="mb-2">
                                            <h6>Order. No</h6>
                                            <p>{{ $order->id ?? '-' }}</p>
                                        </div>
                                        <div class="mb-2">
                                            <h6>Due Date</h6>
                                            <p>{{ $order->created_at->addDays(30)->format('d-m-Y') }}</p>
                                        </div>
                                        <div class="mb-2">
                                            <h6>Sub Total</h6>
                                            <p>${{ number_format($order->total_paid, 2) }}</p>
                                        </div>
                                        
                                    </div>
                                    <div class="ttl-amt py-2 px-3 d-flex justify-content-between align-items-center">
                                        <h6>Total</h6>
                                        <h3 class="text-primary font-weight-700">${{ $order->total_paid  }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Print Button -->
                        <div class="row">
                            <div class="col-12 text-center">
                                <button onclick="window.print()" class="btn btn-primary">
                                    <i class="bi bi-print"></i> Print Invoice
                                </button>
                            </div>
                        </div>

                    </div> <!-- End Card Body -->
                </div> <!-- End Card -->
            </div> <!-- End Col -->
        </div> <!-- End Row -->
    </div> <!-- End Container -->
</div>
<!-- Wrapper End -->

<script>
    window.addEventListener("load", (event) => {
        window.print();
    });
</script>

</body>
</html>
