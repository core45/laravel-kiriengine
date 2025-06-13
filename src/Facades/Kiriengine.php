<?php

namespace Core45\LaravelKiriengine\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Core45\LaravelKiriengine\Kiriengine\Balance balance()
 * @method static \Core45\LaravelKiriengine\Kiriengine\Model3d model3d()
 * @method static \Core45\LaravelKiriengine\Kiriengine\Scan3dgs scan3dgs()
 * @method static \Core45\LaravelKiriengine\Kiriengine\ScanObject scanObject()
 * @method static \Core45\LaravelKiriengine\Kiriengine\PhotoScanUpload photoScanUpload()
 *
 * @see \Core45\LaravelKiriengine\Kiriengine
 */
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
