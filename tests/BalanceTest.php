<?php

use Core45\LaravelKiriengine\Facades\Kiriengine;
use Core45\LaravelKiriengine\Tests\TestCase;
use Illuminate\Support\Facades\Http;

uses(TestCase::class);

beforeEach(function () {
    Http::fake([
        'api.kiriengine.app/api/v1/open/balance' => Http::response([
            'balance' => 100.00,
            'currency' => 'USD'
        ], 200),
    ]);
});

test('it can get balance', function () {
    $response = Kiriengine::balance()->getBalance();

    expect($response)->toBeArray()
        ->toHaveKey('balance')
        ->toHaveKey('currency');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.kiriengine.app/api/v1/open/balance' &&
            $request->hasHeader('Authorization', 'Bearer test_api_key');
    });
});

test('it throws exception on failed request', function () {
    Http::fake([
        'api.kiriengine.app/api/v1/open/balance' => Http::response([
            'code' => 401,
            'msg' => 'Authentication failed'
        ], 401),
    ]);

    expect(fn () => Kiriengine::balance()->getBalance())
        ->toThrow(Exception::class, 'KIRI Engine API Error: {"code":401,"msg":"Authentication failed"}');
}); 