<?php

namespace Core45\LaravelKiriengine\Kiriengine;

use Core45\LaravelKiriengine\Exceptions\KiriengineException;
use Core45\LaravelKiriengine\Services\KiriEngineApiKeyResolver;

class Upload3DgsScan
{
    protected string $baseUrl;
    protected string $apiKey;
    protected bool $debug;

    public function __construct()
    {
        $this->baseUrl = config('laravel-kiriengine.base_url', 'https://api.kiriengine.app/api/v1/open/');
        
        // Use the API key resolver service
        $apiKeyResolver = app(KiriEngineApiKeyResolver::class);
        $this->apiKey = $apiKeyResolver->getApiKey();
        
        $this->debug = config('laravel-kiriengine.debug', false);
    }

    /**
     * Upload images for 3DGS scanning with streaming support using cURL
     *
     * @param array $images Array of image files or file paths
     * @param int $isMesh Turn off/on 3DGS to Mesh (0: Off, 1: On)
     * @param int $isMask Auto Object Masking (0: Off, 1: On)
     * @param string $fileFormat Output format when isMesh=1 (obj, fbx, stl, ply, glb, gltf, usdz, xyz)
     * @return array
     * @throws KiriengineException
     */
    public function imageUpload(
        array $images,
        int $isMesh = 0,
        int $isMask = 0,
        string $fileFormat = 'obj'
    ): array {
        if (count($images) < 20) {
            throw new KiriengineException('At least 20 images are required for 3DGS scanning.');
        }

        if (count($images) > 300) {
            throw new KiriengineException('Maximum 300 images are allowed for 3DGS scanning.');
        }

        $curl = curl_init();

        $parameters = [
            'isMesh' => (string) $isMesh,
            'isMask' => (string) $isMask
        ];

        if ($isMesh == 1) {
            $parameters['fileFormat'] = strtoupper($fileFormat); // API expects uppercase format
        }

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
            CURLOPT_URL => "{$this->baseUrl}/3dgs/image",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $parameters,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 900, // 15 minutes timeout for large uploads
            CURLOPT_VERBOSE => $this->debug,
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
     * Upload video for 3DGS scanning
     *
     * @param string $videoPath Path to video file
     * @param int $isMesh Turn off/on 3DGS to Mesh (0: Off, 1: On)
     * @param int $isMask Auto Object Masking (0: Off, 1: On)
     * @param string $fileFormat Output format (obj, fbx, stl, ply, glb, gltf, usdz, xyz) - only used when isMesh is 1
     * @return array
     * @throws KiriengineException
     */
    public function videoUpload(
        string $videoPath,
        int $isMesh = 0,
        int $isMask = 0,
        string $fileFormat = 'obj'
    ): array {
        if (!file_exists($videoPath)) {
            throw new KiriengineException('Video file not found.');
        }

        $videoInfo = getimagesize($videoPath);
        if ($videoInfo[0] > 1920 || $videoInfo[1] > 1080) {
            throw new KiriengineException('Video resolution must not exceed 1920x1080.');
        }

        $curl = curl_init();

        $parameters = [
            'isMesh' => (string) $isMesh,
            'isMask' => (string) $isMask,
            'fileFormat' => strtoupper($fileFormat), // API expects uppercase format
            'videoFile' => new \CURLFile(
                $videoPath,
                mime_content_type($videoPath) ?: 'video/mp4',
                basename($videoPath)
            ),
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$this->baseUrl}/3dgs/video",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $parameters,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 900, // 15 minutes timeout for large uploads
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_VERBOSE => $this->debug,
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
