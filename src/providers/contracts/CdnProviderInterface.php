<?php

namespace Webravolab\Cdn\Providers\Contracts;

/**
 * Interface ProviderInterface.
 *
 * @author   Paolo Nardini <paolo.nardini@gmail.com>
 */

interface CdnProviderInterface
{
    public function init($configurations);
    public function upload($asset_path, $remote_path = null);
    public function getAssetUrl($asset_path);
    public function delete($remote_path):bool;
    public function exists($assets):bool;
    public function bypass():bool;
    public function overwrite():bool;
    public function checksize():bool;
}