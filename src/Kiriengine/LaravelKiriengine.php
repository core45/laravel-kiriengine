<?php

namespace Core45\LaravelKiriengine\Kiriengine;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

abstract class LaravelKiriengine
{
    protected string $baseUrl;
    protected string $apiKey;
    protected bool $debug;
    protected bool $verify;

    public function __construct()
    {
        $this->baseUrl = Config::get('laravel-kiriengine.base_url', 'https://api.kiriengine.app/api/v1/');
        $this->apiKey = Config::get('laravel-kiriengine.api_key', '');
        $this->debug = Config::get('laravel-kiriengine.debug', false);
        $this->verify = Config::get('laravel-kiriengine.verify', true);

        Log::info('KIRI Engine Config', [
            'baseUrl' => $this->baseUrl,
            'apiKey' => $this->apiKey,
            'endpoint' => $this->getEndpoint()
        ]);
    }

    protected function makeRequest(array $params = []): \Illuminate\Http\Client\Response
    {
        $url = "{$this->baseUrl}{$this->getEndpoint()}";
        $headers = [
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept' => 'application/json',
        ];

        $response = Http::withOptions([
            'debug' => true,
            'verify' => $this->verify,
        ])->withHeaders($headers)->get($url, $params);

        if (!$response->successful()) {
            throw new \Exception("KIRI Engine API Error: " . $response->body());
        }

        return $response;
    }

    abstract protected function getEndpoint(): string;
}
