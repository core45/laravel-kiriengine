<?php

use Core45\LaravelKiriengine\Exceptions\KiriengineException;
use Core45\LaravelKiriengine\Kiriengine\PhotoScanUpload;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Config::set('kiriengine.api_key', 'test-api-key');
    Config::set('kiriengine.api_url', 'https://api.kiriengine.app');
});

test('it throws exception when api key is not set', function () {
    Config::set('kiriengine.api_key', null);

    expect(fn () => new PhotoScanUpload())
        ->toThrow(KiriengineException::class, 'KIRIENGINE_API_KEY is not set in your .env file.');
});

test('it throws exception when image count is less than 20', function () {
    $uploader = new PhotoScanUpload();
    $images = array_fill(0, 19, 'test.jpg');

    expect(fn () => $uploader->imageUpload($images))
        ->toThrow(KiriengineException::class, 'At least 20 images are required for photo scanning.');
});

test('it throws exception when image count is more than 300', function () {
    $uploader = new PhotoScanUpload();
    $images = array_fill(0, 301, 'test.jpg');

    expect(fn () => $uploader->imageUpload($images))
        ->toThrow(KiriengineException::class, 'Maximum 300 images are allowed for photo scanning.');
});

test('it throws exception when video file does not exist', function () {
    $uploader = new PhotoScanUpload();

    expect(fn () => $uploader->videoUpload('non-existent.mp4'))
        ->toThrow(KiriengineException::class, 'Video file not found.');
});

test('it successfully uploads images', function () {
    Http::fake([
        'api.kiriengine.app/api/v1/open/photo/image' => Http::response([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'serialize' => 'test-serial',
                'calculateType' => 1
            ],
            'ok' => true
        ])
    ]);

    $uploader = new PhotoScanUpload();
    $images = array_fill(0, 20, 'test.jpg');

    $result = $uploader->imageUpload(
        images: $images,
        modelQuality: 0,
        textureQuality: 0,
        isMask: 1,
        textureSmoothing: 1,
        fileFormat: 'obj'
    );

    expect($result)->toBe([
        'serialize' => 'test-serial',
        'calculateType' => 1
    ]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.kiriengine.app/api/v1/open/photo/image' &&
            $request->hasHeader('Authorization', 'Bearer test-api-key') &&
            $request['modelQuality'] === 0 &&
            $request['textureQuality'] === 0 &&
            $request['isMask'] === 1 &&
            $request['textureSmoothing'] === 1 &&
            $request['fileFormat'] === 'obj';
    });
});

test('it successfully uploads video', function () {
    Http::fake([
        'api.kiriengine.app/api/v1/open/photo/video' => Http::response([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'serialize' => 'test-serial',
                'calculateType' => 1
            ],
            'ok' => true
        ])
    ]);

    $uploader = new PhotoScanUpload();
    $videoPath = __DIR__ . '/fixtures/test-video.mp4';

    // Create a test video file
    if (!file_exists(dirname($videoPath))) {
        mkdir(dirname($videoPath), 0777, true);
    }
    file_put_contents($videoPath, 'test video content');

    $result = $uploader->videoUpload(
        videoPath: $videoPath,
        modelQuality: 0,
        textureQuality: 0,
        isMask: 1,
        textureSmoothing: 1,
        fileFormat: 'obj'
    );

    expect($result)->toBe([
        'serialize' => 'test-serial',
        'calculateType' => 1
    ]);

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.kiriengine.app/api/v1/open/photo/video' &&
            $request->hasHeader('Authorization', 'Bearer test-api-key') &&
            $request['modelQuality'] === 0 &&
            $request['textureQuality'] === 0 &&
            $request['isMask'] === 1 &&
            $request['textureSmoothing'] === 1 &&
            $request['fileFormat'] === 'obj';
    });

    // Clean up test file
    unlink($videoPath);
    rmdir(dirname($videoPath));
});

test('it handles api error response', function () {
    Http::fake([
        'api.kiriengine.app/api/v1/open/photo/image' => Http::response([
            'code' => 1,
            'msg' => 'API error',
            'ok' => false
        ])
    ]);

    $uploader = new PhotoScanUpload();
    $images = array_fill(0, 20, 'test.jpg');

    expect(fn () => $uploader->imageUpload($images))
        ->toThrow(KiriengineException::class, 'API error: API error (Code: 1)');
});

test('it handles http error response', function () {
    Http::fake([
        'api.kiriengine.app/api/v1/open/photo/image' => Http::response([], 500)
    ]);

    $uploader = new PhotoScanUpload();
    $images = array_fill(0, 20, 'test.jpg');

    expect(fn () => $uploader->imageUpload($images))
        ->toThrow(KiriengineException::class, 'API request failed: 500 - ');
}); 