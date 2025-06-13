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
        $this->baseUrl = Config::get('laravel-kiriengine.base_url', 'https://api.kiriengine.app/api/v1/open/');
        $this->apiKey = Config::get('laravel-kiriengine.api_key', '');

        if (empty($this->apiKey)) {
            throw new \Exception('KIRI Engine API key is not set. Please set KIRIENGINE_API_KEY in your .env file.');
        }

        $this->debug = Config::get('laravel-kiriengine.debug', false);
        $this->verify = Config::get('laravel-kiriengine.verify', true);

        Log::info('KIRI Engine Config', [
            'baseUrl' => $this->baseUrl,
            'apiKey' => $this->apiKey,
            'endpoint' => $this->getEndpoint()
        ]);
    }

    protected function makeRequest(array $params = [], ?string $endpoint = null): \Illuminate\Http\Client\Response
    {
        $url = "{$this->baseUrl}{$this->getEndpoint()}" . ($endpoint ? "/{$endpoint}" : '');
        $headers = [
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept' => 'application/json',
        ];

        Log::info('KIRI Engine Request', [
            'url' => $url,
            'headers' => $headers,
            'params' => $params
        ]);

        $response = Http::withOptions([
            'debug' => true,
            'verify' => $this->verify,
        ])->withHeaders($headers)->get($url, $params);

        Log::info('KIRI Engine Response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if (!$response->successful()) {
            throw new \Exception("KIRI Engine API Error: " . $response->body());
        }

        return $response;
    }

    abstract protected function getEndpoint(): string;
}
