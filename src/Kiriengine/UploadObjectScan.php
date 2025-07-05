<?php

namespace Core45\LaravelKiriengine\Kiriengine;

use Core45\LaravelKiriengine\Exceptions\KiriengineException;
use Core45\LaravelKiriengine\Services\KiriEngineApiKeyResolver;

class UploadObjectScan
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('laravel-kiriengine.base_url', 'https://api.kiriengine.app/api/v1/open/');
        
        // Use the API key resolver service
        $apiKeyResolver = app(KiriEngineApiKeyResolver::class);
        $this->apiKey = $apiKeyResolver->getApiKey();
    }

    /**
     * Upload images for featureless object scanning with streaming support using cURL
     *
     * @param array $images Array of image files or file paths
     * @param string $fileFormat Output format (obj, fbx, stl, ply, glb, gltf, usdz, xyz)
     * @return array
     * @throws KiriengineException
     */
    public function imageUpload(
        array $images,
        string $fileFormat = 'obj'
    ): array {
        if (count($images) < 20) {
            throw new KiriengineException('At least 20 images are required for object scanning.');
        }

        if (count($images) > 300) {
            throw new KiriengineException('Maximum 300 images are allowed for object scanning.');
        }

        $curl = curl_init();

        $parameters = [
            'fileFormat' => strtoupper($fileFormat) // API expects uppercase format
        ];

        foreach ($images as $index => $image) {
            $filePath = $image;

            if (file_exists($filePath)) {
                $parameters["imagesFiles[{$index}]"] = new \CURLFile(
                    $filePath,
                    mime_content_type($filePath) ?: 'image/jpeg',
                    basename($filePath)
                );
            }
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$this->baseUrl}/featureless/image",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $parameters,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 900, // 15 minutes timeout for large uploads
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            throw new KiriengineException("cURL error: {$error}");
        }

        if ($httpCode >= 400) {
            throw new KiriengineException("HTTP error {$httpCode}: {$response}");
        }

        $data = json_decode($response, true);
        
        // Handle response according to official API documentation
        if (!$data || !isset($data['ok'])) {
            throw new KiriengineException("Invalid response from KIRI Engine API: {$response}");
        }

        if (!$data['ok']) {
            throw new KiriengineException("KIRI Engine API error: {$data['msg']} (Code: {$data['code']})");
        }

        return $data['data'] ?? [];
    }

    /**
     * Upload video for object scanning
     *
     * @param string $videoPath Path to video file
     * @param string $fileFormat Output format (obj, fbx, stl, ply, glb, gltf, usdz, xyz)
     * @return array
     * @throws KiriengineException
     */
    public function videoUpload(string $videoPath, string $fileFormat = 'obj'): array
    {
        if (!file_exists($videoPath)) {
            throw new KiriengineException('Video file not found.');
        }

        // Check video resolution and duration
        $videoInfo = getimagesize($videoPath);
        if ($videoInfo[0] > 1920 || $videoInfo[1] > 1080) {
            throw new KiriengineException('Video resolution must not exceed 1920x1080.');
        }

        // Prepare cURL
        $curl = curl_init();

        // Build parameters
        $parameters = [
            'fileFormat' => strtoupper($fileFormat), // API expects uppercase format
            'videoFile' => new \CURLFile(
                $videoPath,
                mime_content_type($videoPath) ?: 'video/mp4',
                basename($videoPath)
            ),
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$this->baseUrl}/featureless/video",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $parameters,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 900, // 15 minutes timeout for large uploads
            CURLOPT_CONNECTTIMEOUT => 30,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            throw new KiriengineException("cURL error: {$error}");
        }

        if ($httpCode >= 400) {
            throw new KiriengineException("HTTP error {$httpCode}: {$response}");
        }

        $data = json_decode($response, true);
        
        // Handle response according to official API documentation
        if (!$data || !isset($data['ok'])) {
            throw new KiriengineException("Invalid response from KIRI Engine API: {$response}");
        }

        if (!$data['ok']) {
            throw new KiriengineException("KIRI Engine API error: {$data['msg']} (Code: {$data['code']})");
        }

        return $data['data'] ?? [];
    }
}
