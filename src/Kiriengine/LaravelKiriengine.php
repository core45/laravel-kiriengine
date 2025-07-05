<?php

namespace Core45\LaravelKiriengine\Kiriengine;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Core45\LaravelKiriengine\Services\KiriEngineApiKeyResolver;

abstract class LaravelKiriengine
{
    protected string $baseUrl;
    protected string $apiKey;
    protected bool $debug;
    protected bool $verify;

    public function __construct()
    {
        $this->baseUrl = Config::get('laravel-kiriengine.base_url', 'https://api.kiriengine.app/api/v1/open/');
        
        // Use the API key resolver service
        $apiKeyResolver = app(KiriEngineApiKeyResolver::class);
        $this->apiKey = $apiKeyResolver->getApiKey();

        $this->debug = Config::get('laravel-kiriengine.debug', false);
        $this->verify = Config::get('laravel-kiriengine.verify', true);

        if ($this->debug) {
            $this->verify = Config::get('laravel-kiriengine.verify', false);

            Log::info('KIRI Engine Config', [
                'baseUrl' => $this->baseUrl,
                'apiKey' => $this->apiKey,
                'endpoint' => $this->getEndpoint()
            ]);
        }
    }

    protected function makeRequest(array $params = [], ?string $endpoint = null): \Illuminate\Http\Client\Response
    {
        $url = rtrim($this->baseUrl, '/');

        if ($endpoint) {
            $url .= "/{$endpoint}";
        }

        $headers = [
            'Authorization' => "Bearer {$this->apiKey}",
            'Accept' => 'application/json',
        ];

        if ($this->debug) {
            Log::info('KIRI Engine Request', [
                'url' => $url,
                'headers' => $headers,
                'params' => $params
            ]);
        }

        $response = Http::withOptions([
            'debug' => $this->debug,
            'verify' => $this->verify,
        ])->withHeaders($headers)->get($url, $params);

        if ($this->debug) {
            Log::info('KIRI Engine Response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }


        if (!$response->successful()) {
            throw new \Exception("KIRI Engine API Error: " . $response->body());
        }

        return $response;
    }

    abstract protected function getEndpoint(): string;
}
