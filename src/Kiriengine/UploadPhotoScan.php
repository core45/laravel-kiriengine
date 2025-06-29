<?php

namespace Core45\LaravelKiriengine\Kiriengine;

use Core45\LaravelKiriengine\Exceptions\KiriengineException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

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

        // Prepare cURL
        $curl = curl_init();

        // Build form data
        $postFields = [
            'modelQuality' => (string) $modelQuality,
            'textureQuality' => (string) $textureQuality,
            'isMask' => (string) $isMask,
            'textureSmoothing' => (string) $textureSmoothing,
            'fileFormat' => $fileFormat,
        ];

        // Add files using CURLFile (streams directly without loading into memory)
        foreach ($images as $index => $image) {
            $filePath = null;
            $fileName = null;
            $mimeType = null;

            if (is_string($image)) {
                // Direct file path
                $filePath = $image;
                $fileName = basename($image);
                $mimeType = mime_content_type($image) ?: 'image/jpeg';
            } elseif (is_array($image)) {
                if (isset($image['path'])) {
                    // File path in array
                    $filePath = $image['path'];
                    $fileName = $image['name'] ?? basename($filePath);
                    $mimeType = $image['mime_type'] ?? mime_content_type($filePath) ?: 'image/jpeg';
                } elseif (isset($image['name']) && isset($image['contents'])) {
                    // Content arrays - create temporary file to avoid memory issues
                    $tempFile = tempnam(sys_get_temp_dir(), 'kiri_');
                    file_put_contents($tempFile, $image['contents']);
                    $filePath = $tempFile;
                    $fileName = $image['name'];
                    $mimeType = $image['mime_type'] ?? mime_content_type($tempFile) ?: 'image/jpeg';
                } else {
                    throw new KiriengineException("Invalid image format at index {$index}. Use file path string, or array with 'path' key, or array with 'name' and 'contents' keys.");
                }
            } else {
                throw new KiriengineException("Invalid image format at index {$index}");
            }

            // Check if file exists
            if (!file_exists($filePath)) {
                if (file_exists(public_path($filePath))) {
                    $filePath = public_path($filePath);
                } else {
                    throw new KiriengineException("File not found at index {$index}: {$filePath}");
                }
            }

            $postFields["imagesFiles[{$index}]"] = new \CURLFile(
                $filePath,
                $mimeType,
                $fileName
            );
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => "{$this->baseUrl}/api/v1/open/photo/image",
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

        // Prepare cURL
        $curl = curl_init();

        // Build form data
        $postFields = [
            'modelQuality' => (string) $modelQuality,
            'textureQuality' => (string) $textureQuality,
            'isMask' => (string) $isMask,
            'textureSmoothing' => (string) $textureSmoothing,
            'fileFormat' => $fileFormat,
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
