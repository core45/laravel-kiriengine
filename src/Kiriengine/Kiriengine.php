<?php

namespace Core45\LaravelKiriengine\Kiriengine;

use Illuminate\Support\Facades\Http;

class Kiriengine {
    private $debug;
    private $verify;
    private string $url = 'https://api.kiriengine.app/api/';
    private string $token;

    public function __construct()
    {
        $this->token = config('kiriengine.key');
        $this->debug = config('kiriengine.debug', false);
        $this->verify = config('kiriengine.verify', true);
    }

    protected function makeRequest(array $parameters): \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
    {
        return Http::withOptions([
            'debug' => $this->debug,
            'verify' => $this->verify,
        ])->withHeaders([
            'X-BLToken' => $this->token,
        ])->asForm()->post($this->url, $parameters);
    }
}
