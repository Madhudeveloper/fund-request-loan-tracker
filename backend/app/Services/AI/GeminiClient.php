<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient implements LlmClient
{
    public function complete(string $purpose, string $system, string $user): LlmResponse
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . config('ai.gemini_model') . ':generateContent';

        $generation = ['maxOutputTokens' => 1000];
        if ($purpose === 'summary') {
            $generation['responseMimeType'] = 'application/json';
        }

        try {
            $res = Http::withHeaders(['x-goog-api-key' => config('ai.gemini_key')])
                ->timeout(config('ai.timeout'))
                ->post($url, [
                    'systemInstruction' => ['parts' => [['text' => $system]]],
                    'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
                    'generationConfig' => $generation,
                ]);
        } catch (ConnectionException $e) {
            Log::warning('llm.timeout', ['purpose' => $purpose]);
            throw new LlmUnavailableException('timeout', 0, $e);
        }

        if ($res->failed()) { // includes 429 rate limits and 5xx server errors
            Log::warning('llm.error', ['purpose' => $purpose, 'status' => $res->status(), 'body' => $res->body()]);
            throw new LlmUnavailableException("status {$res->status()}");
        }

        $out = new LlmResponse(
            (string) $res->json('candidates.0.content.parts.0.text'),
            (int) $res->json('usageMetadata.promptTokenCount'),
            (int) $res->json('usageMetadata.candidatesTokenCount')
        );

        Log::info('llm.usage', [
            'purpose' => $purpose,
            'model' => config('ai.gemini_model'),
            'input_tokens' => $out->inputTokens,
            'output_tokens' => $out->outputTokens,
        ]);

        return $out;
    }
}