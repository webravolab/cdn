<?php

namespace Webravolab\Cdn;

use Illuminate\Support\Facades\App;
use Webravolab\Cdn\Contracts\ProviderFactoryInterface;

/**
 * Class ProviderFactory
 * This class is responsible of creating objects from the default
 * provider found in the config file.
 *
 * @category Factory
 *
 * @author   Paolo Nardini <paolo.nardini@gmail.com>
 */

class ProviderFactory implements ProviderFactoryInterface
{
    const DRIVERS_NAMESPACE = 'Webravolab\\Cdn\\Providers\\';

    /**
     * Create and return an instance of the corresponding
     * Provider concrete according to the configuration.
     *
     * @param array $configurations
     * @return mixed
     *
     */
    public function create($configurations = array())
    {
        // get the default provider name
        $provider = isset($configurations['default']) ? $configurations['default'] : null;

        if (!$provider) {
            throw new \Exception('Missing Configurations: Default Provider');
        }

        // prepare the full driver class name
        $driver_class = self::DRIVERS_NAMESPACE.ucwords($provider).'Provider';

        if (!class_exists($driver_class)) {
            throw new \Exception("CDN provider ($provider) is not supported");
        }

        // initialize the driver object and initialize it with the configurations
        $driver_object = App::make($driver_class)->init($configurations);

        return $driver_object;
    }
}
