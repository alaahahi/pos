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
