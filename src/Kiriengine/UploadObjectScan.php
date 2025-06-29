<?php

namespace Core45\LaravelKiriengine\Kiriengine;

use Core45\LaravelKiriengine\Exceptions\KiriengineException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;

class UploadObjectScan
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
     * Upload images for object scanning with streaming support
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
    public function objectUpload(
        array $images,
        int $modelQuality = 0,
        int $textureQuality = 0,
        int $isMask = 0,
        int $textureSmoothing = 0,
        string $fileFormat = 'obj'
    ): array {
        if (count($images) < 20) {
            throw new KiriengineException('At least 20 images are required for object scanning.');
        }

        if (count($images) > 300) {
            throw new KiriengineException('Maximum 300 images are allowed for object scanning.');
        }

        $client = new Client([
            'timeout' => 300, // 5 minutes timeout for large uploads
            'connect_timeout' => 30,
        ]);

        $multipart = [];
        
        // Add form fields
        $multipart[] = [
            'name' => 'modelQuality',
            'contents' => (string) $modelQuality
        ];
        $multipart[] = [
            'name' => 'textureQuality',
            'contents' => (string) $textureQuality
        ];
        $multipart[] = [
            'name' => 'isMask',
            'contents' => (string) $isMask
        ];
        $multipart[] = [
            'name' => 'textureSmoothing',
            'contents' => (string) $textureSmoothing
        ];
        $multipart[] = [
            'name' => 'fileFormat',
            'contents' => $fileFormat
        ];

        // Add files with streaming
        foreach ($images as $index => $image) {
            if (is_string($image)) {
                // Direct file path - stream directly from disk
                if (file_exists($image)) {
                    $multipart[] = [
                        'name' => 'imagesFiles',
                        'contents' => Utils::streamFor(fopen($image, 'r')),
                        'filename' => basename($image)
                    ];
                } elseif (file_exists(public_path($image))) {
                    $multipart[] = [
                        'name' => 'imagesFiles',
                        'contents' => Utils::streamFor(fopen(public_path($image), 'r')),
                        'filename' => basename($image)
                    ];
                } else {
                    throw new KiriengineException("File not found at index {$index}: {$image}");
                }
            } elseif (is_array($image)) {
                if (isset($image['path'])) {
                    // File path in array - stream directly from disk
                    $filePath = $image['path'];
                    if (file_exists($filePath)) {
                        $multipart[] = [
                            'name' => 'imagesFiles',
                            'contents' => Utils::streamFor(fopen($filePath, 'r')),
                            'filename' => $image['name'] ?? basename($filePath)
                        ];
                    } elseif (file_exists(public_path($filePath))) {
                        $multipart[] = [
                            'name' => 'imagesFiles',
                            'contents' => Utils::streamFor(fopen(public_path($filePath), 'r')),
                            'filename' => $image['name'] ?? basename($filePath)
                        ];
                    } else {
                        throw new KiriengineException("File not found at index {$index}: {$filePath}");
                    }
                } elseif (isset($image['name']) && isset($image['contents'])) {
                    // Content arrays - what KIRI API expects (but already loaded in memory)
                    $multipart[] = [
                        'name' => 'imagesFiles',
                        'contents' => Utils::streamFor($image['contents']),
                        'filename' => $image['name']
                    ];
                } else {
                    throw new KiriengineException("Invalid image format at index {$index}. Use file path string, or array with 'path' key, or array with 'name' and 'contents' keys.");
                }
            } else {
                throw new KiriengineException("Invalid image format at index {$index}");
            }
        }

        $request = new Request(
            'POST',
            "{$this->baseUrl}/api/v1/open/object/image",
            [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'multipart/form-data',
            ]
        );

        $response = $client->send($request, [
            'multipart' => $multipart
        ]);

        return $this->handleGuzzleResponse($response);
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

        $response = Http::withToken($this->apiKey)
            ->attach('videoFile', file_get_contents($videoPath), basename($videoPath))
            ->post("{$this->baseUrl}/api/v1/open/featureless/video", [
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

    /**
     * Handle Guzzle response and throw exceptions if necessary
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return array
     * @throws KiriengineException
     */
    protected function handleGuzzleResponse($response): array
    {
        if ($response->getStatusCode() >= 400) {
            throw new KiriengineException(
                "API request failed: {$response->getStatusCode()} - {$response->getBody()}"
            );
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if (!$data['ok']) {
            throw new KiriengineException(
                "API error: {$data['msg']} (Code: {$data['code']})"
            );
        }

        return $data['data'];
    }
}
