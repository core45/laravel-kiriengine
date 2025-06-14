<?php

namespace Core45\LaravelKiriengine\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KiriWebhookReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $payload;
    public array $headers;

    /**
     * Create a new event instance.
     */
    public function __construct(array $payload, array $headers = [])
    {
        $this->payload = $payload;
        $this->headers = $headers;
    }
}
