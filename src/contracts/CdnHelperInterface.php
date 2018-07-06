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

    public function processImage($path, array $a_params);

    public function uploadImageToCdn($file_name);

    public function makeAssetUrl($file_name);

}