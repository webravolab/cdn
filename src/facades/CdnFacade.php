<?php
namespace Webravolab\Cdn\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class CdnFacade
 *
 * @category Facade Accessor
 *
 */
class CdnFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cdn';
    }
}