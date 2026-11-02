<?php
namespace App\Services\AI;

final class LlmResponse
{
    public function __construct(public string $text, public int $inputTokens = 0, public int $outputTokens = 0) {}
}
