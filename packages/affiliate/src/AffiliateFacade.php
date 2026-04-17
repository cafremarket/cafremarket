<?php

namespace Incevio\Package\Affiliate;

use Illuminate\Support\Facades\Facade;

class AffiliateFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'affiliate';
    }
}
