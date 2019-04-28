<?php

namespace Webravolab\Cdn\Contracts;

use Webravolab\Cdn\Providers\Contracts\CdnProviderInterface;

/**
 * Interface CdnFacadeInterface.
 *
 * @author   Paolo Nardini <paolo.nardini@gmail.com>
 */

interface CdnFacadeInterface
{
    public function image($path, $a_params = []);

    public function upload($path, $remote_path = null);

    public function elixir($path);

    public function getProviderInstance();
}