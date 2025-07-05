<?php

namespace Core45\LaravelKiriengine\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Core45\LaravelKiriengine\Kiriengine setApiKey(string $apiKey)
 * @method static void clearApiKey()
 * @method static \Core45\LaravelKiriengine\Kiriengine\Balance balance()
 * @method static \Core45\LaravelKiriengine\Kiriengine\Model3d model3d()
 * @method static \Core45\LaravelKiriengine\Kiriengine\Upload3DgsScan Upload3DgsScan()
 * @method static \Core45\LaravelKiriengine\Kiriengine\UploadObjectScan uploadObjectScan()
 * @method static \Core45\LaravelKiriengine\Kiriengine\UploadPhotoScan uploadPhotoScan()
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
