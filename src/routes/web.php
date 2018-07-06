<?php

/**
 * CDN UPLOAD/TRANSFER ROUTE
 */

Route::get('/cdn/upload', [
    'as' => 'cdn.upload',
    'uses' => 'Webravolab\Cdn\CdnController@uploadAsset'
]);
