<?php

namespace Core45\LaravelKiriengine\Kiriengine;

use Core45\LaravelKiriengine\Exceptions\KiriengineException;

class UploadPhotoScan
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
     * Upload images for photo scanning with streaming support using cURL
     * Based on official KIRI Engine API documentation: https://docs.kiriengine.app/photo-scan/image-upload
     *
     * @param array $images Array of image files or file paths
     * @param int $modelQuality Model quality (0: High, 1: Medium, 2: Low, 3: Ultra)
     * @param int $textureQuality Texture quality (0: 4K, 1: 2K, 2: 1K, 3: 8K)
     * @param int $isMask Auto Object Masking (0: Off, 1: On)
     * @param int $textureSmoothing Texture Smoothing (0: Off, 1: On)
     * @param string $fileFormat Output format (obj, fbx, stl, ply, glb, gltf, usdz, xyz)
     * @return array
     * @throws KiriengineException
     */
    public function imageUpload(
        array $images,
        int $modelQuality = 0,
        int $textureQuality = 0,
        int $isMask = 0,
        int $textureSmoothing = 0,
        string $fileFormat = 'obj'
    ): array {
        if (count($images) < 20) {
            throw new KiriengineException('At least 20 images are required for photo scanning.');
        }

        if (count($images) > 300) {
            throw new KiriengineException('Maximum 300 images are allowed for photo scanning.');
        }

        $curl = curl_init();

        $parameters = [
            'modelQuality' => (string) $modelQuality,
            'textureQuality' => (string) $textureQuality,
            'isMask' => (string) $isMask,
            'textureSmoothing' => (string) $textureSmoothing,
            'fileFormat' => strtoupper($fileFormat)
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
            CURLOPT_URL => "{$this->baseUrl}/api/v1/open/photo/image",
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
        
        if (!$data || !isset($data['ok'])) {
            throw new KiriengineException("Invalid response from KIRI Engine API: {$response}");
        }

        if (!$data['ok']) {
            throw new KiriengineException("KIRI Engine API error: {$data['msg']} (Code: {$data['code']})");
        }

        return $data['data'] ?? [];
    }

    /**
     * Upload video for photo scanning
     * Based on official KIRI Engine API documentation: https://docs.kiriengine.app/photo-scan/video-upload
     *
     * @param string $videoPath Path to video file
     * @param int $modelQuality Model quality (0: High, 1: Medium, 2: Low, 3: Ultra)
     * @param int $textureQuality Texture quality (0: 4K, 1: 2K, 2: 1K, 3: 8K)
     * @param int $isMask Auto Object Masking (0: Off, 1: On)
     * @param int $textureSmoothing Texture Smoothing (0: Off, 1: On)
     * @param string $fileFormat Output format (obj, fbx, stl, ply, glb, gltf, usdz, xyz)
     * @return array
     * @throws KiriengineException
     */
    public function videoUpload(
        string $videoPath,
        int $modelQuality = 0,
        int $textureQuality = 0,
        int $isMask = 0,
        int $textureSmoothing = 0,
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
            'modelQuality' => (string) $modelQuality,
            'textureQuality' => (string) $textureQuality,
            'isMask' => (string) $isMask,
            'textureSmoothing' => (string) $textureSmoothing,
            'fileFormat' => strtoupper($fileFormat), // API expects uppercase format
            'videoFile' => new \CURLFile(
                $videoPath,
                mime_content_type($videoPath) ?: 'video/mp4',
                basename($videoPath)
            ),
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$this->baseUrl}/api/v1/open/photo/video",
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
        
        if (!$data || !isset($data['ok'])) {
            throw new KiriengineException("Invalid response from KIRI Engine API: {$response}");
        }

        if (!$data['ok']) {
            throw new KiriengineException("KIRI Engine API error: {$data['msg']} (Code: {$data['code']})");
        }

        return $data['data'] ?? [];
    }
}
