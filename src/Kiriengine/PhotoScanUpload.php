<?php

namespace Core45\LaravelKiriengine\Kiriengine;

use Core45\LaravelKiriengine\Exceptions\KiriengineException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PhotoScanUpload
{
    protected string $baseUrl;
    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('kiriengine.api_url', 'https://api.kiriengine.app');
        $this->apiKey = config('kiriengine.api_key');
    }

    /**
     * Upload images for photo scanning
     *
     * @param array $images Array of image files
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

        $response = Http::withToken($this->apiKey)
            ->attach('imagesFiles', $images)
            ->post("{$this->baseUrl}/api/v1/open/photo/image", [
                'modelQuality' => $modelQuality,
                'textureQuality' => $textureQuality,
                'isMask' => $isMask,
                'textureSmoothing' => $textureSmoothing,
                'fileFormat' => $fileFormat,
            ]);

        return $this->handleResponse($response);
    }

    /**
     * Upload video for photo scanning
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

        // Check video resolution and duration
        $videoInfo = getimagesize($videoPath);
        if ($videoInfo[0] > 1920 || $videoInfo[1] > 1080) {
            throw new KiriengineException('Video resolution must not exceed 1920x1080.');
        }

        $response = Http::withToken($this->apiKey)
            ->attach('videoFile', file_get_contents($videoPath), basename($videoPath))
            ->post("{$this->baseUrl}/api/v1/open/photo/video", [
                'modelQuality' => $modelQuality,
                'textureQuality' => $textureQuality,
                'isMask' => $isMask,
                'textureSmoothing' => $textureSmoothing,
                'fileFormat' => $fileFormat,
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
} 