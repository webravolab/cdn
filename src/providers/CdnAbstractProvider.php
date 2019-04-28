<?php

namespace Webravolab\Cdn\Providers;

/**
 * Abstract class CdnAbstractProvider
 *
 * @author   Paolo Nardini <paolo.nardini@gmail.com>
 */
use Webravolab\Cdn\Providers\Contracts\CdnProviderInterface;

abstract class CdnAbstractProvider implements CdnProviderInterface
{
    abstract public function init($configurations);
    abstract public function upload($asset_path, $remote_path = null);
    abstract public function delete($remote_path):bool;
    abstract public function exists($assets):bool;

    /**
     * Return the current status of bypass flag
     * @return bool
     */
    public function bypass():bool
    {
        return $this->_bypass;
    }

    /**
     * Return the current status of bypass_assets flag
     * @return bool
     */
    public function bypass_assets():bool
    {
        return $this->_bypass_assets;
    }

    /**
     * Return the current status of overwrite flag
     * @return bool
     */
    public function overwrite():bool
    {
        return $this->_overwrite;
    }

    /**
     * Return the current status of check_size flag
     * @return bool
     */
    public function checksize():bool
    {
        return $this->_check_size;
    }

    /**
     * Overwrite bypass flag
     * @param bool $bypass
     */
    public function setBypass(bool $bypass)
    {
        $this->_bypass = $bypass;
    }

    /**
     * Overwrite bypass_assets flag
     * @param bool $bypass_assets
     */
    public function setBypassAssets(bool $bypass_assets)
    {
        $this->_bypass_assets = $bypass_assets;
    }



}