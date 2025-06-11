<?php

namespace Core45\LaravelKiriengine\Facades;

use Illuminate\Support\Facades\Facade;

class Kiriengine extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'kiriengine';
    }
}
