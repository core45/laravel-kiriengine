<?php

namespace Core45\LaravelKiriengine\Kiriengine;

use Core45\LaravelKiriengine\Exceptions\KiriengineException;
use Illuminate\Http\Client\Response;

class Model3d extends LaravelKiriengine
{
    /**
     * Get the status of a 3D model
     *
     * @param string $serialize The serial number of the model
     * @return array
     * @throws KiriengineException
     */
    public function getStatus(string $serialize): array
    {
        $response = $this->makeRequest([
            'serialize' => $serialize,
            'endpoint' => 'getStatus'
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Download a 3D model (zipped)
     *
     * @param string $serialize The serial number of the model
     * @return array
     * @throws KiriengineException
     */
    public function download(string $serialize): array
    {
        $response = $this->makeRequest([
            'serialize' => $serialize,
            'endpoint' => 'getModelZip'
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Handle API response and throw exceptions if necessary
     *
     * @param Response $response
     * @return array
     * @throws KiriengineException
     */
    protected function handleResponse(Response $response): array
    {
        if (!$response->successful()) {
            throw new KiriengineException(
                "API request failed: {$response->status()} - {$response->body()}"
            );
        }

        $data = $response->json();

        if (!$data['ok']) {
            throw new KiriengineException(
                "API error: {$data['msg']} (Code: {$data['code']})"
            );
        }

        return $data['data'];
    }

    protected function getEndpoint(): string
    {
        return 'model';
    }
}
