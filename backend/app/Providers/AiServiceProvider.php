<?php

namespace App\Providers;

use App\Services\AI\AnthropicClient;
use App\Services\AI\GeminiClient;
use App\Services\AI\LlmClient;
use App\Services\AI\MockClient;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LlmClient::class, fn () => config('ai.provider') === 'anthropic' && config('ai.api_key')
            ? new AnthropicClient
            : new MockClient);
    }
}