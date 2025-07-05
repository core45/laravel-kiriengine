<?php

namespace Core45\LaravelKiriengine\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

class KiriEngineApiKeyResolver
{
    /**
     * Store for explicitly set API keys (for jobs, commands, etc.)
     */
    protected static ?string $explicitApiKey = null;

    /**
     * Set an explicit API key to use (useful for jobs, commands, etc.)
     * 
     * @param string $apiKey
     * @return void
     */
    public static function setApiKey(string $apiKey): void
    {
        self::$explicitApiKey = $apiKey;
    }

    /**
     * Clear the explicitly set API key
     * 
     * @return void
     */
    public static function clearApiKey(): void
    {
        self::$explicitApiKey = null;
    }

    /**
     * Resolve the API key to use for KIRI Engine requests.
     * 
     * Priority order:
     * 1. Explicitly set API key (for jobs, commands, etc.)
     * 2. Custom resolver function (if configured)
     * 3. Environment variable fallback
     * 
     * @return string|null
     */
    public function resolve(): ?string
    {
        // First, check for explicitly set API key (highest priority)
        if (self::$explicitApiKey !== null) {
            return self::$explicitApiKey;
        }

        // Second, try to get from custom resolver if configured
        $customResolver = Config::get('laravel-kiriengine.api_key_resolver');
        
        if ($customResolver && is_callable($customResolver)) {
            try {
                $apiKey = call_user_func($customResolver);
                if (!empty($apiKey)) {
                    return $apiKey;
                }
            } catch (\Exception $e) {
                // Log the error but continue to fallback
                if (Config::get('laravel-kiriengine.debug', false)) {
                    \Log::warning('KIRI Engine API key resolver failed: ' . $e->getMessage());
                }
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
                'Please set KIRIENGINE_API_KEY in your .env file, configure a custom API key resolver, ' .
                'or explicitly set an API key using KiriEngineApiKeyResolver::setApiKey().'
            );
        }
        
        return $apiKey;
    }
} 