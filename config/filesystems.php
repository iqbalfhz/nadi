<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim((string) env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
         * Operational photos that are evidence, not content: OB cleaning
         * checklists, security patrol and incident photos, courier
         * proof-of-delivery. These used to sit on the 'public' disk, which
         * publishes them at /storage/{media_id}/{file} with no login at all —
         * and media IDs run sequentially, so the whole archive was walkable by
         * anyone who found one.
         *
         * 'serve' => true makes Laravel hand these out through a signed,
         * expiring route instead (Illuminate\Filesystem\ServeFile), which also
         * blocks path traversal and sends no-store + a locked-down CSP. The
         * URL path is deliberately NOT /storage: that one is taken by the
         * public symlink, which the web server answers before PHP ever sees
         * the request.
         */
        'internal' => [
            'driver' => 'local',
            'root' => storage_path('app/internal'),
            'url' => rtrim((string) env('APP_URL', 'http://localhost'), '/').'/internal-media',
            'visibility' => 'private',
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        // Credentials aren't read from here — the 'google' driver is
        // registered in AppServiceProvider to pull them from BackupSettings
        // (DB-backed) instead, so the destination account can be rotated
        // from Pengaturan > Backup Otomatis without a deploy.
        'google' => [
            'driver' => 'google',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
