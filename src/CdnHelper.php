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
        $param_type = 'jpg';
        $param_size = '';
        $param_background = '';
        $param_mode = 'size';
        $param_name = null;
        $width = 0;
        $height = 0;

        try {
            extract($a_params, EXTR_OVERWRITE + EXTR_PREFIX_ALL, 'param');

            if (!empty($param_size)) {
                $a_size = explode('x', $param_size);
                if (count($a_size) < 2 && !isIntegerPositive($a_size[0]) && !isIntegerPositive($a_size[2])) {
                    $width = 0;
                    $height = 0;
                } else {
                    $width = $a_size[0];
                    $height = $a_size[1];
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
                default:
                    if (substr($param_background,0,1) == '#') {
                        $bg = '0x' . substr($param_background,1);
                    }
                    elseif (substr($param_background,0,2) == '0x') {
                        $bg = $param_background;
                    }
                    else {
                        $bg = 'transparent';
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
                    $param_type = 'jpg';
                    break;
            }
            $real_file_name = $this->checkImageFileExists($path);
            if (empty($real_file_name)) {
                // Image file not found // TODO
                return null;
            }
            $real_file_modified_time = filemtime($real_file_name);
            $o_image = Image::open($real_file_name);
            if ($width > 0 && $height > 0) {
                switch ($param_mode) {
                    case 'resize':
                        $o_image->resize($width, $height);
                        break;
                    case 'zoom':
                        $o_image->zoomCrop($width, $height, $bg);
                        break;
                    case 'crop':
                        $o_image->cropResize($width, $height, $bg);
                        break;
                    case 'scale':
                        $o_image->scaleResize($width, $height, $bg);
                        break;
                }
            }
            if (!empty($bg)) {
                $o_image->fillBackground($bg);
            }

            $new_or_updated = true;
            $cache_system = $o_image->getCacheSystem();
            $hash = $o_image->getHash($param_type, $param_quality);
            $cache_file_name = $hash . '.' . $param_type;
            $cache_file_name = $cache_system->getCacheFile($cache_file_name);
            if (file_exists(public_path($cache_file_name))) {
                // Check image last modification date
                $modified_time = filemtime($cache_file_name);
                if ($modified_time >= $real_file_modified_time) {
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
                $file_name = $param_name;
                $pretty_name = public_path($file_name);
                if ($new_or_updated || !file_exists($pretty_name)) {
                    $cache_file_name = $o_image->cacheFile($param_type, $param_quality);
                    $o_image->save($pretty_name, $param_type, $param_quality);
                    // Must be updated on CDN
                    $new_or_updated = true;
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
            return null;
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
            if (isset($a_parts['extension']) && in_array($a_parts['extension'], $this->_image_types)) {
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
}
