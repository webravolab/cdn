<?php

namespace Webravolab\Cdn\Contracts;

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
}