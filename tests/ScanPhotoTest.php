<?php

use Core45\LaravelKiriengine\Facades\Kiriengine;
use Core45\LaravelKiriengine\Tests\TestCase;
use Illuminate\Support\Facades\Http;

uses(TestCase::class);

beforeEach(function () {
    Http::fake([
        'api.kiriengine.app/api/v1/photo-scan' => Http::response([
            'task_id' => 'test_task_123',
            'status' => 'processing'
        ], 200),
    ]);
});

test('it can create photo scan task', function () {
    $photos = [
        'https://example.com/photo1.jpg',
        'https://example.com/photo2.jpg'
    ];

    $options = [
        'quality' => 'high',
        'format' => 'glb'
    ];

    $response = Kiriengine::scanPhoto()->create($photos, $options);

    expect($response)->toBeArray()
        ->toHaveKey('task_id')
        ->toHaveKey('status');

    Http::assertSent(function ($request) use ($photos, $options) {
        return $request->url() === 'https://api.kiriengine.app/api/v1/photo-scan' &&
            $request->hasHeader('Authorization', 'Bearer test_api_key') &&
            $request['photos'] === $photos &&
            $request['options'] === $options;
    });
});

test('it throws exception on failed request', function () {
    Http::fake([
        'api.kiriengine.app/api/v1/photo-scan' => Http::response([
            'code' => 400,
            'msg' => 'Invalid request'
        ], 400),
    ]);

    expect(fn () => Kiriengine::scanPhoto()->create(['invalid_url']))
        ->toThrow(Exception::class, 'KIRI Engine API Error: {"code":400,"msg":"Invalid request"}');
}); 