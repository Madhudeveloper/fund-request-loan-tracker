<?php
namespace App\Services\AI;

interface LlmClient
{
    /** @throws LlmUnavailableException on timeout, 429 or server errors */
    public function complete(string $purpose, string $system, string $user): LlmResponse;
}
