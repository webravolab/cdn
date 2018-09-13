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
    | Overwrite not existing images
    |--------------------------------------------------------------------------
    |
    | This option determines whether to overwrite the CDN image with the fallback
    | image if the given source image is not existing.
    | Set it to "false" to keep the last CDN version of image even if the source
    | has been deleted.
    |
    | Default: true
    |
    */
    'overwrite' => true,


    /*
    |--------------------------------------------------------------------------
    | Default CDN provider
    |--------------------------------------------------------------------------
    |
    */
    'default' => 'Webravo',

    /*
    |--------------------------------------------------------------------------
    | Custom fallback image
    |--------------------------------------------------------------------------
    |
    | A fallback image for not found images
    |
    | (Enter the full path starting from public directory)
    |
    */
    'fallback_image' => '/img/fallback.png',

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
    | Note: Credentials could be set in the .env file:
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
