<?php

namespace Core45\LaravelKiriengine\Listeners;

use Core45\LaravelKiriengine\Events\KiriWebhookReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ProcessKiriWebhook implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes for large file downloads

    /**
     * Handle the event.
     */
    public function handle(KiriWebhookReceived $event): void
    {
        Log::info('Processing KIRI webhook', [
            'payload_size' => count($event->payload),
            'headers_count' => count($event->headers)
        ]);

        // Process your webhook data here
        $this->processWebhookData($event->payload);
    }

    /**
     * Process the webhook data
     */
    private function processWebhookData(array $payload): void
    {
        // Check if this is a model processing completion webhook
        if (isset($payload['id']) || isset($payload['model_id'])) {
            $id = $payload['id'] ?? $payload['model_id'];
            $this->downloadAndSaveModel($id);
        } else {
            Log::warning('KIRI webhook received without model ID', ['payload' => $payload]);
        }
    }

    /**
     * Download and save the 3D model from KIRI Engine
     */
    private function downloadAndSaveModel(string $id): void
    {
        try {
            Log::info('Starting model download', ['model_id' => $id]);

            // Get download information from KIRI Engine
            $result = \Kiriengine::model3d()->download($id);

            if (!isset($result['modelUrl']) || !isset($result['serialize'])) {
                Log::error('Invalid response from KIRI API', [
                    'model_id' => $id,
                    'result' => $result
                ]);
                return;
            }

            $modelUrl = $result['modelUrl'];
            $serialize = $result['serialize'];

            Log::info('Downloading model file', [
                'model_id' => $id,
                'serialize' => $serialize,
                'url' => $modelUrl
            ]);

            // Download the zip file with extended timeout
            $response = Http::timeout(300)->get($modelUrl);

            if (!$response->successful()) {
                Log::error('Failed to download model file', [
                    'model_id' => $id,
                    'status' => $response->status(),
                    'url' => $modelUrl
                ]);
                return;
            }

            // Save zip to temporary location
            $tempZipPath = tempnam(sys_get_temp_dir(), 'kiri_');
            if (!$tempZipPath) {
                throw new \Exception('Failed to create temporary file');
            }

            file_put_contents($tempZipPath, $response->body());
            Log::info('Downloaded zip file to temp location', [
                'model_id' => $id,
                'temp_path' => $tempZipPath,
                'size' => filesize($tempZipPath)
            ]);

            // Create destination directory
            $extractPath = "received3d/{$serialize}";
            Storage::makeDirectory($extractPath);

            // Extract zip file
            $this->extractZipFile($tempZipPath, $extractPath, $id);

            // Clean up temporary file
            if (file_exists($tempZipPath)) {
                unlink($tempZipPath);
            }

            Log::info('Model processing completed successfully', [
                'model_id' => $id,
                'serialize' => $serialize,
                'extract_path' => $extractPath
            ]);

        } catch (\Exception $e) {
            Log::error('Error processing model download', [
                'model_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to trigger job failure handling
        }
    }

    /**
     * Extract zip file contents to storage
     */
    private function extractZipFile(string $tempZipPath, string $extractPath, string $modelId): void
    {
        $zip = new ZipArchive();
        $result = $zip->open($tempZipPath);

        if ($result !== TRUE) {
            throw new \Exception("Failed to open zip file. Error code: {$result}");
        }

        $extractedFiles = [];

        try {
            // Extract each file
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                // Skip directories and hidden files
                if (substr($filename, -1) === '/' || strpos($filename, '__MACOSX') !== false) {
                    continue;
                }

                $fileContent = $zip->getFromIndex($i);
                if ($fileContent === false) {
                    Log::warning('Failed to extract file from zip', [
                        'model_id' => $modelId,
                        'filename' => $filename
                    ]);
                    continue;
                }

                $filePath = "{$extractPath}/{$filename}";
                Storage::put($filePath, $fileContent);
                $extractedFiles[] = $filename;

                Log::debug('Extracted file', [
                    'model_id' => $modelId,
                    'filename' => $filename,
                    'size' => strlen($fileContent)
                ]);
            }

            Log::info('Zip extraction completed', [
                'model_id' => $modelId,
                'extracted_files' => $extractedFiles,
                'total_files' => count($extractedFiles),
                'storage_path' => "storage/app/{$extractPath}"
            ]);

        } finally {
            $zip->close();
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(KiriWebhookReceived $event, \Throwable $exception): void
    {
        Log::error('KIRI webhook processing failed', [
            'error' => $exception->getMessage(),
            'payload' => $event->payload,
            'attempts' => $this->attempts()
        ]);

        // You could add additional failure handling here:
        // - Send notification to administrators
        // - Update database record with failure status
        // - etc.
    }
}
