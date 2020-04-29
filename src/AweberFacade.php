<?php

namespace CodeGreenCreative\Aweber;

use Illuminate\Support\Facades\Facade;

class AweberFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'aweber';
    }
}
