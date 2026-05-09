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
        'expenses',
        'daily_closes',
        'monthly_closes',
        'monthly_accounts',
        'customer_balances',
        'supplier_balances',
        'product_price_history',
        'employee_commissions',
        'cashbox_transactions',
        'transactions_images',
    ],

];
