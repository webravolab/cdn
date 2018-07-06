<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Bypass loading assets from the CDN
    |--------------------------------------------------------------------------
    |
    | This option determines whether to load the assets from localhost or from
    | the CDN server. (this is useful during development).
    | Set it to "true" to load from localhost, or set it to "false" to load
    | from the CDN (on production).
    |
    | Default: false
    |
    */
    'bypass' => false,

    /*
    |--------------------------------------------------------------------------
    | Default CDN provider
    |--------------------------------------------------------------------------
    |
    */
    'default' => 'Webravo',


    /*
    |--------------------------------------------------------------------------
    | Files to Include
    |--------------------------------------------------------------------------
    |
    | Specify which directories to be uploaded when running the
    | [$ php artisan cdn:push] command
    |
    | Enter the full paths of directories (starting from the application root).
    |
    */
    'include' => [
        'directories' => ['public'],
        'extensions' => [],
        'patterns' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Files to Exclude
    |--------------------------------------------------------------------------
    |
    | Specify what to exclude from the 'include' directories when uploading
    | to the CDN.
    |
    | 'hidden' is a boolean to excludes "hidden" directories and files (starting with a dot)
    |
    */
    'exclude' => [
        'directories' => [],
        'files' => [],
        'extensions' => [],
        'patterns' => [],
        'hidden' => true,
    ],


    /*
    |--------------------------------------------------------------------------
    | CDN Providers specific configurations
    |--------------------------------------------------------------------------
    |
    | Note: Credentials must be set in the .env file:
    |         CDN_WEBRAVO_KEY
    |         CDN_WEBRAVO_PASSWORD
    |
    */
    'providers' => [
        'Webravo' => [
            'url' => 'http://www.cdn.test',
            'upload_url' => 'http://www.cdn.test/cdn/upload',
        ],
    ],

];
