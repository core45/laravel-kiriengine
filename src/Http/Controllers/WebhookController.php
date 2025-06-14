<?php

namespace Core45\LaravelKiriengine\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Core45\LaravelKiriengine\Events\KiriWebhookReceived;

class WebhookController extends Controller
{
    protected string $secret;

    public function __construct() {
        $this->secret = Config::get('laravel-kiriengine.webhook.secret', '');
        $this->webhook_path = Config::get('laravel-kiriengine.webhook.path', '');

        if (empty($this->secret)) {
            throw new \Exception('KIRI Engine webhook secret is not set. Please set KIRIENGINE_WEBHOOK_SECRET in your .env file.');
        }

        if (empty($this->webhook_path)) {
            throw new \Exception('KIRI Engine webhook path is not set. If you have KIRIENGINE_WEBHOOK_PATH in your .env file please check if is set correctly.');
        }
    }

    public function handle(Request $request)
    {
        $signature = $request->header('x-signature');

        if (!$signature || $signature !== $this->secret) {
            Log::warning('KIRI Engine webhook: Invalid signature', [
                'signature' => $signature,
                'content' => $request->getContent()
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        try {
            $payload = $request->all();
            $headers = $request->headers->all();

            // Fire the event
            KiriWebhookReceived::dispatch($payload, $headers);

            Log::info('KIRI Engine webhook processed successfully', [
                'payload_keys' => array_keys($payload)
            ]);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('KIRI Engine webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

//    protected function verifySignature(string $payload, string $signature, string $secret): bool
//    {
//        $expectedSignature = hash_hmac('sha256', $payload, $secret);
//        return hash_equals($expectedSignature, $signature);
//    }
}
