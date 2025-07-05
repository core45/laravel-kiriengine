<?php

namespace Core45\LaravelKiriengine\Traits;

use Core45\LaravelKiriengine\Services\KiriEngineApiKeyResolver;

trait WithKiriEngineApiKey
{
    /**
     * Set the KIRI Engine API key for this job/command
     * 
     * @param string $apiKey
     * @return $this
     */
    public function withKiriEngineApiKey(string $apiKey): self
    {
        KiriEngineApiKeyResolver::setApiKey($apiKey);
        return $this;
    }

    /**
     * Set the KIRI Engine API key from a user model
     * 
     * @param \Illuminate\Foundation\Auth\User $user
     * @return $this
     */
    public function withUserKiriEngineApiKey($user): self
    {
        if ($user && property_exists($user, 'kiri_token') && $user->kiri_token) {
            KiriEngineApiKeyResolver::setApiKey($user->kiri_token);
        }
        return $this;
    }

    /**
     * Set the KIRI Engine API key from user ID
     * 
     * @param int $userId
     * @return $this
     */
    public function withUserIdKiriEngineApiKey(int $userId): self
    {
        $user = \App\Models\User::find($userId);
        if ($user) {
            $this->withUserKiriEngineApiKey($user);
        }
        return $this;
    }

    /**
     * Clear the explicitly set API key
     * 
     * @return void
     */
    protected function clearKiriEngineApiKey(): void
    {
        KiriEngineApiKeyResolver::clearApiKey();
    }
} 