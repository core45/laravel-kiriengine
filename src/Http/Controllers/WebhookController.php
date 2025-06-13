<?php

namespace Core45\LaravelKiriengine\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Verify webhook signature if secret is configured
        if ($secret = Config::get('laravel-kiriengine.webhook.secret')) {
            $signature = $request->header('X-Kiri-Signature');
            
            if (!$signature || !$this->verifySignature($request->getContent(), $signature, $secret)) {
                Log::warning('KIRI Engine webhook: Invalid signature', [
                    'signature' => $signature,
                    'content' => $request->getContent()
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        try {
            $data = $request->all();
            
            // Store the webhook data
            $storagePath = Config::get('laravel-kiriengine.webhook.storage_path');
            $filename = sprintf(
                '%s/%s_%s.json',
                $storagePath,
                $data['task_id'] ?? 'unknown',
                date('Y-m-d_His')
            );

            Storage::put($filename, json_encode($data, JSON_PRETTY_PRINT));

            // Log the webhook
            Log::info('KIRI Engine webhook received', [
                'task_id' => $data['task_id'] ?? 'unknown',
                'status' => $data['status'] ?? 'unknown',
                'stored_at' => $filename
            ]);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('KIRI Engine webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    protected function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }
} 