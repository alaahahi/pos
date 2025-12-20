<?php

return [
    /*
    |--------------------------------------------------------------------------
    | License System Configuration
    |--------------------------------------------------------------------------
    |
    | إعدادات نظام الترخيص والتفعيل للمنتج
    |
    */

    // تفعيل/تعطيل نظام الترخيص
    'enabled' => env('LICENSE_ENABLED', true),

    // التحقق من الترخيص عند كل طلب (قد يؤثر على الأداء)
    'check_on_every_request' => env('LICENSE_CHECK_EVERY_REQUEST', false),

    // فترة السماح بعد انتهاء الترخيص (بالأيام)
    'grace_period_days' => env('LICENSE_GRACE_PERIOD', 7),

    // فترة التحقق الدوري (بالثواني)
    'verification_interval' => env('LICENSE_VERIFICATION_INTERVAL', 3600), // ساعة واحدة

    // دعم التفعيل Offline
    'offline_mode' => env('LICENSE_OFFLINE_MODE', true),

    // مسار ملف الترخيص
    'license_file' => storage_path('app/license.key'),

    // Secret Key لتوقيع الترخيص (يجب تغييره في الإنتاج)
    'secret_key' => env('LICENSE_SECRET_KEY', 'your-secret-key-change-this'),

    // URL للتحقق Online (اختياري)
    'verification_url' => env('LICENSE_VERIFICATION_URL', null),

    // السماح بتعدد التثبيتات لنفس المفتاح
    'allow_multiple_installations' => env('LICENSE_ALLOW_MULTIPLE', false),

    // Routes التي لا تحتاج ترخيص
    'excluded_routes' => [
        'license.activate',
        'license.status',
        'login',
        'register',
    ],

    // Controllers التي لا تحتاج ترخيص
    'excluded_controllers' => [
        'LicenseController',
    ],
];

