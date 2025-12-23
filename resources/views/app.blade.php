<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title > {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- Favicons -->
    <link href="{{ asset('dashboard-assets/img/favicon.png') }}" rel="icon">
    <link href="{{ asset('dashboard-assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">


    <!-- Google Fonts -->
    <link href="https://fonts.gstatic.com" rel="preconnect">
    <link
        href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i"
        rel="stylesheet">

    <!-- Vendor CSS Files -->
    <link href="{{ asset('dashboard-assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('dashboard-assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('dashboard-assets/vendor/boxicons/css/boxicons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('dashboard-assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
    <link href="{{ asset('dashboard-assets/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
    <link href="{{ asset('dashboard-assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
    <link href="{{ asset('dashboard-assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">

    <!-- Template Main CSS File -->
    <link href="{{ asset('dashboard-assets/css/style.css') }}" rel="stylesheet">

    @if (app()->getLocale() === 'ar')
        <link rel="stylesheet" href="{{ asset('dashboard-assets/css/style-ltr.css') }}">
        <link href='https://fonts.googleapis.com/css?family=Cairo' rel='stylesheet'>
    @else
    <link rel="stylesheet" href="{{ asset('dashboard-assets/css/style-rtl.css') }}">
    @endif
   
    <!-- Scripts -->
    @routes
    <script>
        window.translations = @json(__('messages'));
        window.permissions = @json(auth()->user() ? auth()->user()->getAllPermissions()->pluck('name') : []);
        
        // إعدادات الاتصال - من ملف .env
        window.connectionInfo = {
            online_url: @json(env('ONLINE_URL', 'https://system.intellijapp.com/dashboard')),
            local_url: @json(env('LOCAL_URL', 'http://127.0.0.1:8000/'))
        };
    </script>
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased bg-gray-100 dark:bg-gray-800">
 {{-- Copyright (c) 2024 @MogahidGaffar --}}

    @inertia


   

    <!-- Vendor JS Files -->
    <script src="{{ asset('dashboard-assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('dashboard-assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('dashboard-assets/vendor/chart.js/chart.umd.js') }}"></script>
    <script src="{{ asset('dashboard-assets/vendor/echarts/echarts.min.js') }}"></script>
    <script src="{{ asset('dashboard-assets/vendor/quill/quill.js') }}"></script>
    <script src="{{ asset('dashboard-assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
    <script src="{{ asset('dashboard-assets/vendor/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('dashboard-assets/vendor/php-email-form/validate.js') }}"></script>
    <!-- Template Main JS File -->
    <script src="{{ asset('dashboard-assets/js/main.js') }}"></script>



<style>
  .modal-mask {
    position: fixed;
    z-index: 9998;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: table;
    transition: opacity 0.3s ease;
  }
  
  .modal-wrapper {
    display: table-cell;
    vertical-align: middle;
  }
  
  .modal-container {
    width: 50%;
    min-width: 350px;
    margin: 0px auto;
    padding: 20px  30px;
    padding-bottom: 60px;
    background-color: #fff;
    border-radius: 2px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.33);
    transition: all 0.3s ease;
    border-radius: 10px;
  }
  
  .modal-header h3 {
    margin-top: 0;
    color: #42b983;
  }
  
  .modal-body {
    margin: 20px 0;
  }
  
  .modal-default-button {
    float: right;
    width: 100%;
    color: #fff;
  }
  
  /*
   * The following styles are auto-applied to elements with
   * transition="modal" when their visibility is toggled
   * by Vue.js.
   *
   * You can easily play with the modal transition by editing
   * these styles.
   */
  
  .modal-enter-from {
    opacity: 0;
  }
  
  .modal-leave-to {
    opacity: 0;
  }
  
  .modal-enter-from .modal-container,
  .modal-leave-to .modal-container {
    -webkit-transform: scale(1.1);
    transform: scale(1.1);
  }

  .card-body {
  padding: 20px 20px 20px 20px !important;
}

textarea.form-control {
    border-radius: 10px !important;
    border: 1px   #000 !important;
  }
  .modal-header {
    justify-content: center !important;
  }
  </style>
</body>
</html>
