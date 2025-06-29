<?php

namespace Core45\LaravelKiriengine\Kiriengine;

use Core45\LaravelKiriengine\Exceptions\KiriengineException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Upload3DgsScan
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('kiriengine.api_url', 'https://api.kiriengine.app');
        $this->apiKey = config('kiriengine.api_key');

        if (empty($this->apiKey)) {
            throw new KiriengineException('KIRIENGINE_API_KEY is not set in your .env file.');
        }
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

        // Prepare cURL
        $curl = curl_init();

        // Build form data
        $postFields = [
            'isMesh' => (string) $isMesh,
            'isMask' => (string) $isMask
        ];

        // Add fileFormat only when isMesh is enabled
        if ($isMesh == 1) {
            $postFields['fileFormat'] = $fileFormat;
        }

        // Add files using CURLFile (streams directly without loading into memory)
        foreach ($images as $index => $image) {
            $filePath = $image;

            if (file_exists($filePath)) {
                $postFields["imagesFiles[{$index}]"] = new \CURLFile(
                    $filePath,
                    mime_content_type($filePath) ?: 'image/jpeg',
                    basename($filePath)
                );
            }
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$this->baseUrl}/api/v1/open/3dgs/image",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
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

        return json_decode($response, true) ?: [];
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

        // Check video resolution and duration
        $videoInfo = getimagesize($videoPath);
        if ($videoInfo[0] > 1920 || $videoInfo[1] > 1080) {
            throw new KiriengineException('Video resolution must not exceed 1920x1080.');
        }

        // Prepare cURL
        $curl = curl_init();

        // Build form data
        $postFields = [
            'isMesh' => (string) $isMesh,
            'isMask' => (string) $isMask,
            'fileFormat' => $fileFormat,
            'videoFile' => new \CURLFile(
                $videoPath,
                mime_content_type($videoPath) ?: 'video/mp4',
                basename($videoPath)
            ),
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$this->baseUrl}/api/v1/open/3dgs/video",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
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

        return json_decode($response, true) ?: [];
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
        if ($response->failed()) {
            throw new KiriengineException(
                'KIRI Engine API request failed: ' . $response->body(),
                $response->status()
            );
        }

        $data = $response->json();

        if (isset($data['error'])) {
            throw new KiriengineException('KIRI Engine API error: ' . $data['error']);
        }

        return $data;
    }
}
