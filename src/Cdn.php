<?php

namespace Webravolab\Cdn;

/**
 * Class Cdn
 *
 * @author   Paolo Nardini <paolo.nardini@gmail.com>
 */

use Webravolab\Cdn\Contracts\CdnFacadeInterface;
use Webravolab\Cdn\Contracts\CdnHelperInterface;
use Webravolab\Cdn\Contracts\ProviderFactoryInterface;
use Image;

class Cdn implements CdnFacadeInterface
{
    protected $_helper;
    protected $_provider_factory;
    protected $_provider;
    protected $_configuration;
    protected $_manifest = null;

    protected $test;

    public function __construct(
        CdnHelperInterface $helper,
        ProviderFactoryInterface $provider_factory )
    {
        $this->_helper = $helper;
        $this->_provider_factory = $provider_factory;
    }

    public function init()
    {
        if (!$this->_provider) {
            if (!$this->_configuration) {
                $this->_configuration = $this->_helper->getConfiguration();
            }
            $this->_provider = $this->_provider_factory->create($this->_configuration);
        }
    }

    /**
     * Return the current provider instance
     *
     * @return CdnProviderInterface
     */
    public function getProviderInstance()
    {
        $this->init();
        return $this->_provider;
    }

    /**
     * Return full URL of an image asset
     *
     * @param $path
     * @param array $a_params
     * @return string or null
     */
    public function image($path, $a_params = []) {
        $this->init();
        $path = $this->_helper->processImage($path, $a_params);
        return $path;
    }

    /**
     * Upload a local image to remote CDN
     * @param $path
     * @param null $remote_path
     * @return bool
     */
    public function upload($path, $remote_path = null)
    {
        $this->init();
        $this->_provider->upload($path, $remote_path);
        return true;
    }

    /**
     * Return full URL of an elixir asset
     *
     * @param $path
     * @return mixed
     * @throws \Exception
     */
    public function elixir($path)
    {
        if (is_null($this->_manifest)) {
            $this->_manifest = json_decode(file_get_contents(public_path('build/rev-manifest.json')), true);
        }
        if (isset($this->_manifest[$path])) {
            $asset_path = 'build/' . $this->_manifest[$path];
            // If bypass_assets is true, return the local assets
            if (!$this->_helper->getProviderByPassAssets()) {
                return $this->_helper->makeAssetUrl($asset_path);
            }
            else {
                return '/' . $asset_path;
            }
        }
        throw new \Exception("File {$path} not defined in asset manifest.");
    }

    public function getRemoteImagePath($image_name)
    {
        $this->init();
        return $this->_helper->makeAssetUrl($image_name);
    }

}
