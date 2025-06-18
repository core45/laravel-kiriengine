<?php

namespace Core45\LaravelKiriengine;

use Core45\LaravelKiriengine\Kiriengine\Balance;
use Core45\LaravelKiriengine\Kiriengine\Model3d;
use Core45\LaravelKiriengine\Kiriengine\Upload3DgsScan;
use Core45\LaravelKiriengine\Kiriengine\UploadObjectScan;
use Core45\LaravelKiriengine\Kiriengine\UploadPhotoScan;

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

    public function upload3DgsScan(): Upload3DgsScan
    {
        return new Upload3DgsScan();
    }

    public function uploadObjectScan(): UploadObjectScan
    {
        return new UploadObjectScan();
    }

    public function uploadPhotoScan(): UploadPhotoScan
    {
        return new UploadPhotoScan();
    }
}
