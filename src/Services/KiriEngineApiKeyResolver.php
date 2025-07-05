<?php

namespace Core45\LaravelKiriengine\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

class KiriEngineApiKeyResolver
{
    /**
     * Resolve the API key to use for KIRI Engine requests.
     * 
     * This method can be easily overridden in the consuming application
     * to support multi-tenant scenarios where API keys are stored per user.
     * 
     * @return string|null
     */
    public function resolve(): ?string
    {
        // First, try to get from custom resolver if configured
        $customResolver = Config::get('laravel-kiriengine.api_key_resolver');
        
        if ($customResolver && is_callable($customResolver)) {
            $apiKey = call_user_func($customResolver);
            if (!empty($apiKey)) {
                return $apiKey;
            }
        }
        
        // Fall back to environment variable
        return Config::get('laravel-kiriengine.api_key');
    }
    
    /**
     * Check if the API key is available.
     * 
     * @return bool
     */
    public function hasApiKey(): bool
    {
        return !empty($this->resolve());
    }
    
    /**
     * Get the API key or throw an exception if not available.
     * 
     * @return string
     * @throws \Exception
     */
    public function getApiKey(): string
    {
        $apiKey = $this->resolve();
        
        if (empty($apiKey)) {
            throw new \Exception(
                'KIRI Engine API key is not set. ' .
                'Please set KIRIENGINE_API_KEY in your .env file or configure a custom API key resolver.'
            );
        }
        
        return $apiKey;
    }
} 