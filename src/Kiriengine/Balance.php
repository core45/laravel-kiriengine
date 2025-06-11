<?php

namespace Core45\LaravelKiriengine\Kiriengine;

class Balance extends LaravelKiriengine
{
    public function getBalance()
    {
        $response = $this->makeRequest();

        return $response->json();
    }

    protected function getEndpoint(): string
    {
        return 'open/balance';
    }
}
