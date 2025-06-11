<?php

namespace Core45\LaravelKiriengine\Kiriengine;

class Balance extends LaravelKiriengine
{
    public function getBalance()
    {
        $response = $this->makeRequest([
            'method' => __FUNCTION__,
        ]);

        return $response->json();
    }

    protected function getEndpoint(): string
    {
        return 'balance';
    }
}
