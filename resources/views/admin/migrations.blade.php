<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>إدارة المايكريشنات - POS System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .main-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
        }
        .btn-action {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
        }
        .migration-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            transition: all 0.2s;
        }
        .migration-item:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-card">
            <!-- Header -->
            <div class="card-header-custom text-center">
                <h1 class="mb-0">
                    <i class="bi bi-database-gear me-2"></i>
                    إدارة المايكريشنات
                </h1>
                <p class="mb-0 mt-2">تشغيل وإدارة قاعدة البيانات</p>
            </div>

            <div class="card-body p-4">
                <!-- Actions -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <form action="{{ route('admin.migrations.run', ['key' => 'migrate123']) }}" method="POST">
                            <button type="submit" class="btn btn-success btn-action w-100">
                                <i class="bi bi-play-circle me-2"></i>
                                تشغيل المايكريشنات
                            </button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form action="{{ route('admin.migrations.rollback', ['key' => 'migrate123']) }}" method="POST" onsubmit="return confirm('هل أنت متأكد؟')">
                            <button type="submit" class="btn btn-warning btn-action w-100">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>
                                تراجع آخر دفعة
                            </button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form action="{{ route('admin.migrations.refresh', ['key' => 'migrate123']) }}" method="POST" onsubmit="return confirm('سيتم حذف جميع البيانات! هل أنت متأكد؟')">
                            <button type="submit" class="btn btn-danger btn-action w-100">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                إعادة تشغيل الكل
                            </button>
                        </form>
                    </div>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Migrations Status -->
                <div class="row g-4">
                    <!-- Pending Migrations -->
                    <div class="col-md-6">
                        <div class="card border-warning">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="bi bi-hourglass-split me-2"></i>
                                    المايكريشنات المعلقة ({{ count($migrations['pending'] ?? []) }})
                                </h5>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                @if(!empty($migrations['pending']))
                                    @foreach($migrations['pending'] as $migration)
                                        <div class="migration-item">
                                            <i class="bi bi-file-earmark-code text-warning me-2"></i>
                                            <small class="text-muted">{{ $migration['migration'] }}</small>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-center text-muted">لا توجد مايكريشنات معلقة</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Executed Migrations -->
                    <div class="col-md-6">
                        <div class="card border-success">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">
                                    <i class="bi bi-check-circle me-2"></i>
                                    المايكريشنات المنفذة ({{ count($migrations['executed'] ?? []) }})
                                </h5>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                @if(!empty($migrations['executed']))
                                    @foreach($migrations['executed'] as $migration)
                                        <div class="migration-item">
                                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                                            <small class="text-muted">{{ $migration['migration'] }}</small>
                                            <span class="badge bg-secondary float-end">Batch: {{ $migration['batch'] }}</span>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-center text-muted">لا توجد مايكريشنات منفذة</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables Info -->
                @if(!empty($tables))
                <div class="mt-4">
                    <h5 class="mb-3">
                        <i class="bi bi-table me-2"></i>
                        الجداول الموجودة ({{ count($tables) }})
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>اسم الجدول</th>
                                    <th>عدد الصفوف</th>
                                    <th>الحجم</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tables as $table)
                                    <tr>
                                        <td><i class="bi bi-table me-2"></i>{{ $table['name'] }}</td>
                                        <td><span class="badge bg-info">{{ number_format($table['rows']) }}</span></td>
                                        <td>{{ $table['size'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <!-- Footer -->
                <div class="mt-4 text-center">
                    <p class="text-muted">
                        <i class="bi bi-shield-check me-2"></i>
                        مفتاح الوصول مطلوب: <code>?key=migrate123</code>
                    </p>
                    <p class="text-info">
                        <i class="bi bi-info-circle me-2"></i>
                        استخدم الرابط: <code>http://127.0.0.1:8000/admin/migrations?key=migrate123</code>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

