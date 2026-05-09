<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Structure-only tables (pull: sync schema only, no data)
    |--------------------------------------------------------------------------
    |
    | عند السحب من السيرفر: تُنشأ البنية فقط ولا تُنسخ البيانات (طابور، توكنز، إلخ).
    |
    */
    'structure_only_tables' => [
        'sync_metadata',
        'sync_queue',
        'sync_id_mapping',
        'failed_jobs',
        'jobs',
        'password_reset_tokens',
        'personal_access_tokens',
    ],

    /*
    |--------------------------------------------------------------------------
    | No-push tables (pull from server only, never push local to server)
    |--------------------------------------------------------------------------
    |
    | تُنزّل بياناتها من السيرفر عند المزامنة، لكن لا يُرفع منها شيء من اللوكل إلى السيرفر.
    | يشمل: migrations، جداول Spatie (permissions/roles)، وجداول structure_only أعلاه.
    |
    */
    'no_push_tables' => [
        'migrations',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'permissions',
        'roles',
        'sync_metadata',
        'sync_queue',
        'sync_id_mapping',
        'failed_jobs',
        'jobs',
        'password_reset_tokens',
        'personal_access_tokens',
    ],

    /*
    |--------------------------------------------------------------------------
    | UUID tables (primary key is UUID for sync; no id mapping needed)
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Queue sync observer models (generic SyncQueueModelObserver)
    |--------------------------------------------------------------------------
    |
    | Models listed here enqueue changes to sync_queue on create/update/delete.
    | User, Order, and Spatie Role/Permission keep their dedicated observers.
    | Set queue_in_console=true only if you need CLI changes to enqueue (rare).
    |
    */
    'queue_in_console' => env('SYNC_QUEUE_IN_CONSOLE', false),

    /*
    |--------------------------------------------------------------------------
    | حدود طلبات المزامنة عبر HTTP (429)
    |--------------------------------------------------------------------------
    |
    | monitor_api_rate_per_minute: على السيرفر، مسار api/sync-monitor/* كان يخضع لـ
    | throttle:api (60/د) فيسبب 429 عند رفع الطابور. يُستبدل بـ throttle:sync-monitor.
    |
    | api_429_max_attempts: على العميل، عدد إعادات POST بعد استلام 429 مع تأخير متزايد.
    |
    */
    'monitor_api_rate_per_minute' => (int) env('SYNC_MONITOR_API_RATE_PER_MINUTE', 1200),
    'api_429_max_attempts' => max(1, (int) env('SYNC_API_429_MAX_ATTEMPTS', 10)),

    'sync_queue_observer_models' => [
        \App\Models\Product::class,
        \App\Models\Category::class,
        \App\Models\Customer::class,
        \App\Models\Supplier::class,
        \App\Models\Wallet::class,
        \App\Models\Box::class,
        \App\Models\Transactions::class,
        \App\Models\PurchaseInvoice::class,
        \App\Models\PurchaseInvoiceItem::class,
        \App\Models\Decoration::class,
        \App\Models\DecorationTeam::class,
        \App\Models\DecorationOrder::class,
        \App\Models\SimpleDecorationOrder::class,
        \App\Models\Expense::class,
        \App\Models\DailyClose::class,
        \App\Models\MonthlyClose::class,
        \App\Models\MonthlyAccount::class,
        \App\Models\CustomerBalance::class,
        \App\Models\SupplierBalance::class,
        \App\Models\ProductPriceHistory::class,
        \App\Models\EmployeeCommission::class,
        \App\Models\CashboxTransaction::class,
        \App\Models\TransactionsImages::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Conflict policy (documentation)
    |--------------------------------------------------------------------------
    |
    | Recommended: server wins for writes applied via api-sync from local push;
    | last successful write on same row should match UUID across devices.
    | Keep migrations aligned on local SQLite and server MySQL.
    |
    */
    'conflict_policy' => 'server_authoritative_api',

    'uuid_tables' => [
        'users',
        'categories',
        'suppliers',
        'customers',
        'products',
        'wallets',
        'boxes',
        'orders',
        'order_items',
        'order_product',
        'transactions',
        'purchase_invoices',
        'purchase_invoice_items',
        'decorations',
        'decoration_teams',
        'decoration_orders',
        'simple_decoration_orders',
    ],

    /*
    |--------------------------------------------------------------------------
    | إدراج عند فشل التحديث 404 (الصف غير موجود على MySQL)
    |--------------------------------------------------------------------------
    |
    | يصل اللوكل بطلب update بـ id محلي بينما السيرفر لم يستقبل insert بعد أو
    | تختلف المفاتيح؛ نُعيد المحاولة كـ insert لإنشاء الصف ثم يُحفظ التخطيط.
    |
    */
    'upsert_on_update_404_tables' => [
        'daily_closes',
        'monthly_closes',
        'monthly_accounts',
    ],

];
