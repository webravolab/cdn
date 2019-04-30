# CDN Images and Assets Manager for Laravel

##### Content Delivery Network Package for Laravel

The package handle automatic image processing and upload to a remote CDN.

Two remote CDN providers are included, a custom one and the Google Storage Bucket driver, but you can implement your own driver to interface with any standard CDN like AWS, Cloudflare ...

#### Laravel Support
- Tested with Laravel 5.2
- Not tested with higher Laravel versions 

## Usage

Use the Cdn facade to interact, for example from your blade template: 

```blade
<img src="{{ Cdn::image('/img/image.png') }}"/>
```
*simplest example without any option* 

```blade
<img src="{{ Cdn::image('/img/source-image.png', ['name' => '/images/cdn-image.png', 'type' => 'png', 'mode' => 'resize', 'size' => '150x100', 'background' => 'black', 'overwrite' => true]) }}"/>
```
*complex image transform and custom name*

### Image processing options:

The full sintax is:

```php
Cdn::image($source_file, array $options)
``` 

Source file must be a file name path relative to public directory.

Options are a combination of the following:

- name: the path and name where the image must be uploaded to the Cdn
- mode: image processing, one of the following
    - resize
    - forceresize
    - zoom
    - zoomcrop
    - crop
    - cropresize
    - scale
    - resizecanvas
    - cropauto
- type: image output type, one of "png","jpg","jpeg","gif", could be different from source image type causing type conversion.    
- size: the desidered size of resulting image, formatted as [nnn]x[nnn] (Es. 100x150)
- position: for processing requiring a start position (crop etc.) the start position formatted as [nnn]-[nnn] or [nnn]x[nnn]  
- background: one of "white", "black" or "transparent", or a #rrggbb color code to set as image background
- quality: a value 0-100 for images handling compression (png / jpg)
- overwrite: true/false to bypass config parameter
- checksize: true/false to bypass config parameter
        
     

## Installation

#### Via Composer

Require `webravolab/cdn` in your project:

```bash 
composer require webravolab/cdn
```

To use the Google Storage driver you must also install the Google Api Client and Webravolab Layers:

```bash 
composer require google/apiclient:^2.2
composer require webravolab/layers:^1.0
```

You must register manually the service provider:

Add the service provider to `config/app.php`:

```php
'providers' => array(
     //...
     Webravolab\Cdn\CdnServiceProvider::class,
),
```

Publish the default configuration file:

```bash
php artisan vendor:publish webravolab/cdn
```

To use the Google Storage driver you must add the following configuration variables to your environment:

```
GOOGLE_APPLICATION_NAME="<your app name>"
GOOGLE_CLIENT_ID="<your client id>"
GOOGLE_CLIENT_SECRET="<your secret key>"
GOOGLE_DEVELOPER_KEY="<your developer key>"
GOOGLE_REDIRECT="urn:ietf:wg:oauth:2.0:oob"
GOOGLE_SERVICE_ENABLED=true
GOOGLE_APPLICATION_CREDENTIALS="<absolute path of your service account configuration json file>"
GOOGLE_STORAGE_DEFAULT_BUCKET="<default bucket name>"
GOOGLE_STORAGE_IMAGE_CACHE_TTL=86400
```

## Dependencies

This package depends on the wonderful [gregwar/image](https://github.com/Gregwar/Image) to process images and [guzzlehttp/guzzle](https://github.com/guzzlehttp/guzzle) 
to manage assets upload to cdn.

## Configuration

Configuration is copied at `config/webravo_cdn.php`

##### Default Provider
```php
'default' => 'Webravo',
```
or 
```php
'default' => 'GoogleStorage',
```

##### CDN Provider Configuration

For Webravo provider, you must define just your cdn url and specific url to upload assets.
For Google Storage provider you must define the remote bucket name, the default TTL, and the storage URL to pre-pend to assets.

Optionally, you can define another bucket (cdn_bucket) to use when creating assets url, in case you handle separate buckets for upload and retrieve. If omitted, the same bucket  name is used.

```php
'providers' => [
    'Webravo' => [
        'url' => 'https://www.my-cdn.com',
        'upload_url' => 'https://www.my-cdn.com/cdn/upload',
    ],
    'GoogleStorage' => [
        'bucket' => '<bucket>',
        'ttl' => '86400'
        'url' => 'https://storage.googleapis.com',
        'cdn_bucket' => '<bucket>',
    ]],
```

##### Bypass

To disable CDN and load your assets from local machine for testing purposes, set the `bypass` option to `true`:

```php
'bypass' => true,
```

##### Don't overwrite CDN images if source is missing 
 
As per default, missing images are replaced by a fallback image (overwrite = true by default). 
To change this behaviour set overwrite to false in your configuration file. 

```php
'overwrite' => false,
```

Overwrite flag could be passed also as optional parameter to any Cdn:: call.

##### Check for file size changes 

 As per default, file size is not checked to detect image changes, but only modification date is used. 
 To enable file size check set checksize to true in your configuration file. 
 
 ```php
 'checksize' => true,
 ```
Checksize flag could be passed also as optional parameter to any Cdn:: call.

### Elixir assets

*(Webravo provider only)*

```blade
{{Cdn::elixir('assets/js/main.js')}}        // example result: https://www.my-cdn.com/build/assets/js/main-85cafe36ff.js

{{Cdn::elixir('assets/css/style.css')}}        // example result: https://www.my-cdn.com/build/assets/css/style-2d558139f2.css
```

*Note: the `elixir` works the same as the Laravel `elixir` it loads the manifest.json file from build folder and choose the correct file revision generated by  gulp:*

*Note: currently, the `elixir` command does not copy assets to Cdn, but simply add/replace the url with the Cdn url* 

## Test

No test available.

## Support

[On Github](https://github.com/Webravolab/cdn/issues)


## Contributing

- This is a heavy customized package for Webravo internal use. You should fork it and try to customize to fit your needs.

## Credits

- Heavily inspired by [Vinelab/cdn](https://github.com/Vinelab/cdn) package for general code organization and facade interface.

## License

The MIT License (MIT). Please see [License File](https://github.com/Webravolab/cdn/blob/master/LICENSE) for more information.
 