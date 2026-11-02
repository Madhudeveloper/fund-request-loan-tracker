<?php
namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\{Http, Log};

class AnthropicClient implements LlmClient
{
    public function complete(string $purpose, string $system, string $user): LlmResponse
    {
        try {
            $res = Http::withHeaders(['x-api-key' => config('ai.api_key'), 'anthropic-version' => '2023-06-01'])
                ->timeout(config('ai.timeout'))
                ->post('https://api.anthropic.com/v1/messages', [
                    'model' => config('ai.model'),
                    'max_tokens' => config('ai.max_output_tokens'),
                    'system' => $system,
                    'messages' => [['role' => 'user', 'content' => $user]],
                ]);
        } catch (ConnectionException $e) {
            Log::warning('llm.timeout', ['purpose' => $purpose]);
            throw new LlmUnavailableException('timeout', 0, $e);
        }

        if ($res->status() === 429 || $res->serverError() || $res->failed()) {
            Log::warning('llm.error', ['purpose' => $purpose, 'status' => $res->status()]);
            throw new LlmUnavailableException("status {$res->status()}");
        }

        $out = new LlmResponse((string) $res->json('content.0.text'), (int) $res->json('usage.input_tokens'), (int) $res->json('usage.output_tokens'));
        Log::info('llm.usage', ['purpose' => $purpose, 'model' => config('ai.model'), 'input_tokens' => $out->inputTokens, 'output_tokens' => $out->outputTokens]);
        return $out;
    }
}
