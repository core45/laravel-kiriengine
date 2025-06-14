<?php

namespace Core45\LaravelKiriengine\Kiriengine;

class UploadObjectScan extends LaravelKiriengine
{
    public function create(array $photos, array $options = [])
    {
        $response = $this->makeRequest([
            'photos' => $photos,
            'options' => $options,
        ]);

        return $response->json();
    }

    protected function getEndpoint(): string
    {
        return 'featureless-object-scan';
    }
}
