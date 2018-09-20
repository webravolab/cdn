<?php

namespace Webravolab\Cdn;

/**
 * Class CdnHelper
 *
 * @author   Paolo Nardini <paolo.nardini@gmail.com>
 */

use Webravolab\Cdn\Contracts\CdnHelperInterface;
use Webravolab\Cdn\Contracts\ProviderFactoryInterface;
use Gregwar\Image\Image as Image;
use Illuminate\Config\Repository;
use Log;

class CdnHelper implements CdnHelperInterface
{
    private $_image_types = [
        'png',
        'jpg',
        'jpeg',
        'gif'
    ];

    protected $config;
    protected $_configurations;
    protected $_provider_factory;
    protected $_provider;

    /**
     * @param \Illuminate\Config\Repository $configurations
     */
    public function __construct(
        Repository $configurations,
        ProviderFactoryInterface $provider_factory
    )
    {
        $this->_configurations = $configurations;
        $this->_provider_factory = $provider_factory;
        $this->initProvider();
    }

    public function initProvider()
    {
        if (!$this->_provider) {
            $this->_provider = $this->_provider_factory->create($this->_configurations->get('webravo_cdn'));
        }
    }

    public function getConfiguration() {
        $configuration = $this->_configurations->get('webravo_cdn');
        if (!$configuration) {
            throw new \Exception("CDN 'config file' (webravo_cdn.php) not found");
        }
        return $configuration;
    }

    /**
     * Process a local image and if changed upload it to Cdn server
     *
     * @param $path
     * @param array $a_params
     * @return mixed|null|string
     */
    public function processImage($path, array $a_params = []) {

        // default parameters
        $param_quality = 90;
        $param_type = '';
        $param_size = '';
        $param_position = '';
        $param_background = '';
        $param_mode = 'size';
        $param_name = null;
        $param_crop_mode = 'auto';
        $param_overwrite = $this->_provider->overwrite();
        $param_checksize = $this->_provider->checksize();
        $param_threshold = 0.5;
        $width = 0;
        $height = 0;
        $posX = 0;
        $posY = 0;
        $is_animated_gif = false;
        $is_fallback = false;

        try {
            extract($a_params, EXTR_OVERWRITE + EXTR_PREFIX_ALL, 'param');

            $real_file_name = $this->checkImageFileExists($path);
            if (empty($real_file_name)) {
                // Image not found ...
                if ($param_overwrite) {
                    // Overwrite mode - get fallback image
                    $is_fallback = true;
                    $fallback = $this->getConfiguration()['fallback_image'];
                    if (!empty($fallback)) {
                        $real_file_name = $this->checkImageFileExists($fallback);
                        if (empty($real_file_name)) {
                            // Last chance ... use Image fallback
                            $o_tmp = Image::create(100, 100);
                            $real_file_name = $o_tmp->getFallback();
                        }
                    }
                }
                else {
                    // Ovewrite mode disabled ... return the cdn image name without any check
                    $file_name = $this->checkExtension($param_name, $param_type);
                    return $this->makeAssetUrl($file_name);
                }
            }
            // Check image last modified date and image extension
            $real_file_modified_time = filemtime($real_file_name);
            $real_extension = $this->getImageExtension($path);

            if (!empty($param_size)) {
                $a_size = explode('x', $param_size);
                if (count($a_size) < 2 && !isIntegerPositive($a_size[0]) && !isIntegerPositive($a_size[2])) {
                    $width = 0;
                    $height = 0;
                } else {
                    $width = 0 + $a_size[0];
                    $height = 0 + $a_size[1];
                }
            }
            if (!empty($param_position)) {
                $param_position = str_replace('x',',', $param_position);
                $param_position = str_replace(';',',', $param_position);
                $param_position = str_replace('-',',', $param_position);
                $a_size = explode(',', $param_position);
                if (count($a_size) < 2) {
                    $posX = 0;
                    $posY = 0;
                } else {
                    $posX = $a_size[0];
                    $posY = $a_size[1];
                }
            }
            if (empty($param_quality) || $param_quality == 0) {
                $param_quality = 90;
            }
            switch ($param_background) {
                case 'white':
                    $bg = 0xffffff;
                    break;
                case 'black':
                    $bg = 0x000000;
                    break;
                case 'transparent':
                    $bg = $param_background;
                    break;
                default:
                    if (substr($param_background,0,1) == '#') {
                        $bg = '0x' . substr($param_background,1);
                    }
                    elseif (substr($param_background,0,2) == '0x') {
                        $bg = $param_background;
                    }
                    else {
                        $bg = $param_background;
                    }
                    break;
            }
            switch (strtolower($param_type)) {
                case 'png':
                case 'jpg':
                case 'jpeg':
                case 'gif':
                    $param_type = strtolower($param_type);
                    break;
                default:
                    if (empty($real_extension)) {
                        // Force jpg for unknown image types
                        $param_type = 'jpg';
                    }
                    else {
                        $param_type = $real_extension;
                    }
                    break;
            }

            if ($param_type == 'gif' || $real_extension == 'gif') {
                // Check for animated gif
                $is_animated_gif = $this->isGifAnimated($real_file_name);
            }

            $o_image = Image::open($real_file_name);
            $o_image->setAdapter(new Adapters\GD_extended());

            if (!$is_animated_gif) {
                // ANIMATED GIF! Don't process with Gregwar/image and copy "as is"
                // <TODO> Extract and process any single frame
                // see https://stackoverflow.com/questions/718491/resize-animated-gif-file-without-destroying-animation

                if ($width > 0 && $height > 0) {
                    switch (strtolower($param_mode)) {
                        case 'resize':
                            $o_image->resize($width, $height, $bg);
                            break;
                        case 'forceresize':
                            $o_image->forceResize($width, $height, $bg);
                            break;
                        case 'zoom':
                            $o_image->zoomCrop($width, $height, $bg);
                            break;
                        case 'zoomcrop':
                            $o_image->zoomCrop($width, $height, $bg, $posX, $posY);
                            break;
                        case 'crop':
                            $o_image->crop($posX, $posY, $width, $height);
                            break;
                        case 'cropresize':
                            $o_image->cropResize($width, $height, $bg);
                            break;
                        case 'scale':
                        case 'scaleresize':
                            $o_image->scaleResize($width, $height, $bg);
                            break;
                        case 'resizecanvas':
                            $o_image->resizeCanvas($width, $height, $posX, $posY, $bg);
                            break;
                        case 'cropauto':
                            if (!empty($param_threshold)) {
                                $param_threshold = 0 + $param_threshold;
                            }
                            $o_image->cropAuto($param_crop_mode, $param_threshold, $bg);
                            $o_image->fillBackground($bg);
                            $o_image->resizeCanvas($width, $height, $posX, $posY, $bg);
                            break;
                    }
                }
                if (!empty($bg) && $bg != 'transparent') {
                    $o_image->fillBackground($bg);
                }
            }
            $new_or_updated = true;
            $cache_system = $o_image->getCacheSystem();
            $hash = $o_image->getHash($param_type, $param_quality);
            $cache_file_name = $hash . '.' . $param_type;
            $cache_file_name = $cache_system->getCacheFile($cache_file_name);
            if (file_exists(public_path($cache_file_name))) {
                // Check image last modification date
                $modified_time = filemtime($cache_file_name);
                if (!$is_fallback && $modified_time >= $real_file_modified_time) {
                    // Processed image is already up to date
                    $new_or_updated = false;
                    $file_name = $cache_file_name;
                }
            }
            if (empty($param_name)) {
                // No custom image name ... use cache name
                if ($new_or_updated) {
                    $file_name = $o_image->cacheFile($param_type, $param_quality);
                }
            }
            else {
                // Use a custom image name
                $file_name = $this->checkExtension($param_name, $param_type);
                $pretty_name = public_path($file_name);
                if ($param_checksize && !$is_animated_gif && !$is_fallback) {
                    // If enabled, check also for file size changes
                    if (!$new_or_updated && file_exists($pretty_name)) {
                        $cache_size = filesize($cache_file_name);
                        $pretty_size = filesize($pretty_name);
                        if ($cache_size != $pretty_size) {
                            Log::debug('Cdn: image size changes');
                            Log::debug($cache_file_name . ' - ' . $cache_size . ' - date: ' . date(DATE_RFC822, $modified_time));
                            Log::debug($pretty_name . ' - ' . $pretty_size . ' - date: ' . date(DATE_RFC822, $real_file_modified_time));
                            $new_or_updated = true;
                        }
                    }
                }
                if ($new_or_updated || !file_exists($pretty_name)) {
                    $cache_file_name = $o_image->cacheFile($param_type, $param_quality);
                    if ($is_animated_gif) {
                        // ANIMATED GIF! Don't process with Gregwar/image and copy "as is"
                        // <TODO> Extract and process any single frame
                        // see https://stackoverflow.com/questions/718491/resize-animated-gif-file-without-destroying-animation
                        // Simply copy the file
                        $this->assureDirectoryExists($pretty_name);
                        copy($real_file_name, $pretty_name);
                    }
                    else {
                        // Copy the file from cache ... don't save it again - 2018-08-20 <PN>
                        $this->assureDirectoryExists($pretty_name);
                        copy($cache_file_name, $pretty_name);
                        // $o_image->save($pretty_name, $param_type, $param_quality);
                    }
                    // Must be updated on CDN
                    $new_or_updated = true;
                    if ($is_fallback) {
                        // Set a very old modification time for fallback images to allow overwrite next time
                        touch($cache_file_name, 1000000);
                    }
                }
            }
            if ($new_or_updated && !$this->_provider->bypass()) {
                // Upload image to CDN if has been changed
                $file_name = $this->uploadImageToCdn($file_name);
                return $file_name;
            }
            else {
                return $this->makeAssetUrl($file_name);
            }
        }
        catch (\Exception $e) {
            Log::error('CdnHelper / processImage Error:' . $e->getMessage());
            $fallback = $this->getConfiguration()['fallback_image'];
            if (!empty($fallback)) {
                return $fallback;
            }
            return 'error.jpg';
        }
    }

    /**
     * Wrapper to provider upload
     * @param $file_name
     * @return mixed
     */
    public function uploadImageToCdn($file_name) {
        return $this->_provider->upload($file_name);
    }

    /**
     * Wrapper to provider getAssetUrl
     * @param $file_name
     * @return mixed
     */
    public function makeAssetUrl($file_name) {
        $this->initProvider();
        return $this->_provider->getAssetUrl($file_name);
    }

    /**
     * Check for any possible image type and return the first matching
     * @param string $path
     * @return null|string
     */
    protected function checkImageFileExists(string $path):?string
    {
        try {
            $a_parts = pathinfo($path);
            if (isset($a_parts['extension']) && in_array(strtolower($a_parts['extension']), $this->_image_types)) {
                $real_file_name = public_path($path);
                if (file_exists($real_file_name)) {
                    return $real_file_name;
                }
            }
            foreach ($this->_image_types as $type) {
                $real_file_name = public_path($a_parts['dirname'] . '/' . $a_parts['filename'] . '.' . $type);
                if (file_exists($real_file_name)) {
                    return $real_file_name;
                }
            }
            return null;
        }
        catch (\Exception $e) {
            return null;
        }
    }

    protected function getImageExtension($file_name): ?string
    {
        try {
            // Extract real image extension from path
            $a_parts = pathinfo($file_name);
            if (isset($a_parts['extension']) && in_array(strtolower($a_parts['extension']), $this->_image_types)) {
                return strtolower($a_parts['extension']);
            }
            return null;
        }
        catch (\Exception $e) {
            return null;
        }
    }

    protected function checkExtension($file_name, $extension) {
        $a_parts = pathinfo($file_name);
        if (isset($a_parts['extension']) && in_array($a_parts['extension'], $this->_image_types)) {
            if (!empty($extension) && $a_parts['extension'] != $extension) {
                // Replace file name extension
                $file_name = str_ireplace($a_parts['extension'], $extension, $file_name);
            }
        }
        return $file_name;
    }

    protected function assureDirectoryExists($file_name)
    {
        $directory = dirname($file_name);
        if (!is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }
        return;
    }

    protected function isGifAnimated($file_name):int
    {
        // See: http://php.net/manual/en/function.imagecreatefromgif.php#104473
        if(!($fh = @fopen($file_name, 'rb'))) {
            return false;
        }
        $count = 0;

        //an animated gif contains multiple "frames", with each frame having a
        //header made up of:
        // * a static 4-byte sequence (\x00\x21\xF9\x04)
        // * 4 variable bytes
        // * a static 2-byte sequence (\x00\x2C) (some variants may use \x00\x21 ?)

        // We read through the file til we reach the end of the file, or we've found at least 2 frame headers
        while(!feof($fh) && $count < 2) {
            $chunk = fread($fh, 1024 * 100); //read 100kb at a time
            $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
        }
        fclose($fh);
        return $count > 1;
    }
}
