<?php

namespace Core45\LaravelKiriengine;

use Core45\LaravelKiriengine\Kiriengine\Balance;
use Core45\LaravelKiriengine\Kiriengine\Model3d;
use Core45\LaravelKiriengine\Kiriengine\Upload3DgsScan;
use Core45\LaravelKiriengine\Kiriengine\UploadObjectScan;
use Core45\LaravelKiriengine\Kiriengine\UploadPhotoScan;
use Core45\LaravelKiriengine\Services\KiriEngineApiKeyResolver;

class Kiriengine
{
    /**
     * Set the API key for subsequent KIRI Engine operations.
     * 
     * @param string $apiKey
     * @return static
     */
    public static function setApiKey(string $apiKey): static
    {
        KiriEngineApiKeyResolver::setApiKey($apiKey);
        return new static();
    }

    /**
     * Clear the explicitly set API key.
     * 
     * @return void
     */
    public static function clearApiKey(): void
    {
        KiriEngineApiKeyResolver::clearApiKey();
    }

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
