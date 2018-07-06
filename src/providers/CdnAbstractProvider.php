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
    abstract public function delete($remote_path);
    abstract public function exists($assets):bool;

}