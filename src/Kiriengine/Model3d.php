<?php

namespace Core45\LaravelKiriengine\Kiriengine;

class Model3d extends LaravelKiriengine
{
    public function getStatus(string $taskId)
    {
        $response = $this->makeRequest([
            'task_id' => $taskId,
        ]);

        return $response->json();
    }

    protected function getEndpoint(): string
    {
        return 'model';
    }
}
