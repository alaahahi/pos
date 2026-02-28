<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Structure-only tables (sync schema, no data)
    |--------------------------------------------------------------------------
    |
    | These tables are created in SQLite during init/sync so they exist locally,
    | but their data is NOT copied. Use for system tables (migrations, queues,
    | tokens, etc.) to avoid conflicts and keep local DB clean.
    |
    */
    'structure_only_tables' => [
        'migrations',
        'sync_metadata',
        'sync_queue',
        'sync_id_mapping',
        'failed_jobs',
        'jobs',
        'password_reset_tokens',
        'personal_access_tokens',
    ],

];
