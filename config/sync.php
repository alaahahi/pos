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

];
