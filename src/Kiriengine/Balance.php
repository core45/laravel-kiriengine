<?php

namespace Core45\LaravelKiriengine\Kiriengine;

class Balance {
    public function getBalance()
    {
        $response = $this->makeRequest([
            'method' => __FUNCTION__,
        ]);

        return $response->json();
    }
}
