<?php

namespace Core45\LaravelKiriengine\Kiriengine;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

abstract class LaravelKiriengine
{
    protected string $baseUrl;
    protected string $apiKey;
    protected bool $debug;
    protected bool $verify;

    public function __construct()
    {
        $this->baseUrl = Config::get('laravel-kiriengine.base_url');
        $this->apiKey = Config::get('laravel-kiriengine.api_key');
        $this->debug = Config::get('laravel-kiriengine.debug', false);
        $this->verify = Config::get('laravel-kiriengine.verify', true);
    }

    protected function makeRequest(array $params = []): \Illuminate\Http\Client\Response
    {
        $response = Http::withOptions([
//            'debug' => $this->debug,
            'debug' => true,
            'verify' => $this->verify,
        ])->withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept' => 'application/json',
        ])->post("{$this->baseUrl}{$this->getEndpoint()}", $params);

        if (!$response->successful()) {
            throw new \Exception("KIRI Engine API Error: " . $response->body());
        }

        return $response;
    }

    abstract protected function getEndpoint(): string;
}
