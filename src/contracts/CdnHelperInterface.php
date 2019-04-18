<?php

namespace Webravolab\Cdn\Contracts;

/**
 * Interface CdnHelperInterface.
 *
 * @author   Paolo Nardini <paolo.nardini@gmail.com>
 */

use Gregwar\Image\Image as Image;

interface CdnHelperInterface
{
    public function getConfiguration();

    public function initProvider();

    public function getProviderByPass(): bool;

    public function getProviderByPassAssets(): bool;

    public function processImage($path, array $a_params);

    public function uploadImageToCdn($file_name, $remote_file_name = null);

    public function removeImageFromCdn($remote_file_name):bool;

    public function makeAssetUrl($file_name);

}