<?php
namespace Webravolab\Cdn\Contracts;

/**
 * Interface ProviderFactoryInterface.
 *
 * @author   Paolo Nardini <paolo.nardini@gmail.com>
 */
interface ProviderFactoryInterface
{
    public function create($configurations);
}