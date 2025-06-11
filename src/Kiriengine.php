<?php

namespace Core45\LaravelKiriengine;

use Core45\LaravelBaselinker\Baselinker\Catalog;
use Core45\LaravelBaselinker\Baselinker\ExternalStorage;
use Core45\LaravelBaselinker\Baselinker\Order;
use Core45\LaravelBaselinker\Baselinker\Shipment;
use Core45\LaravelKiriengine\Kiriengine\Balance;
use Core45\LaravelKiriengine\Kiriengine\Model3d;
use Core45\LaravelKiriengine\Kiriengine\Scan3dgs;
use Core45\LaravelKiriengine\Kiriengine\ScanObject;
use Core45\LaravelKiriengine\Kiriengine\ScanPhoto;

class Kiriengine
{
    public function balance(): Balance
    {
        return new Balance();
    }

    public function model3d(): Model3d
    {
        return new Model3d();
    }

    public function scan3dgs(): Scan3dgs
    {
        return new Scan3dgs();
    }

    public function scanObject(): ScanObject
    {
        return new ScanObject();
    }

    public function scanPhoto(): ScanPhoto
    {
        return new ScanPhoto();
    }
}
