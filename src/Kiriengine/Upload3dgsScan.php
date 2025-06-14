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
     * Upload images for 3DGS scanning
     *
     * @param array $images Array of image files
     * @param int $isMesh Turn off/on 3DGS to Mesh (0: Off, 1: On)
     * @param int $isMask Auto Object Masking (0: Off, 1: On)
     * @param string $fileFormat Output format (obj, fbx, stl, ply, glb, gltf, usdz, xyz) - only used when isMesh is 1
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

        $request = Http::withToken($this->apiKey);

        foreach ($images as $index => $image) {
            if (is_string($image) && file_exists(public_path($image))) {
                $request->attach(
                    "imagesFiles",
                    file_get_contents(public_path($image)),
                    basename($image)
                );
            } elseif (is_array($image) && isset($image['name']) && isset($image['contents'])) {
                $request->attach(
                    "imagesFiles",
                    $image['contents'],
                    $image['name']
                );
            } else {
                throw new KiriengineException("Invalid image format at index {$index}");
            }
        }

        $response = $request->post("{$this->baseUrl}/api/v1/open/3dgs/image", [
            'isMesh' => $isMesh,
            'isMask' => $isMask,
            'fileFormat' => $fileFormat,
        ]);

        return $this->handleResponse($response);
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

        $response = Http::withToken($this->apiKey)
            ->attach('videoFile', file_get_contents($videoPath), basename($videoPath))
            ->post("{$this->baseUrl}/api/v1/open/3dgs/video", [
                'isMesh' => $isMesh,
                'isMask' => $isMask,
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
