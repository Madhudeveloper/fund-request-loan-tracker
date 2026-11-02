<?php
namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

/** Offline stand-in so the app runs without an API key. */
class MockClient implements LlmClient
{
    public function complete(string $purpose, string $system, string $user): LlmResponse
    {
        $text = $purpose === 'summary' ? $this->summary($user) : $this->answer($user);
        Log::info('llm.usage', ['purpose' => $purpose, 'model' => 'mock', 'input_tokens' => (int) (strlen($user) / 4), 'output_tokens' => (int) (strlen($text) / 4)]);
        return new LlmResponse($text, (int) (strlen($user) / 4), (int) (strlen($text) / 4));
    }

    private function summary(string $doc): string
    {
        preg_match('/name[:\s]+([A-Za-z ]{2,40})/i', $doc, $n);
        preg_match('/income[^0-9]{0,20}([\d,]+)/i', $doc, $i);
        preg_match('/employer[:\s]+([^\n]{2,60})/i', $doc, $e);
        return json_encode([
            'applicant_name' => isset($n[1]) ? trim($n[1]) : null,
            'monthly_income' => isset($i[1]) ? (float) str_replace(',', '', $i[1]) : null,
            'employer' => isset($e[1]) ? trim($e[1]) : null,
            'key_risks' => ['Mock summary: verify figures manually'],
            'summary' => 'Automatic mock summary. Connect a real LLM key to analyse this document in depth.',
        ]);
    }

    private function answer(string $prompt): string
    {
        $q = strtolower(substr($prompt, (int) strrpos($prompt, 'Question:')));
        $field = str_contains($q, 'outstanding') ? 'Outstanding' : (str_contains($q, 'repaid') || str_contains($q, 'paid') ? 'Repaid' : (str_contains($q, 'status') ? 'Status' : (str_contains($q, 'amount') ? 'Amount' : null)));
        if ($field && preg_match("/^{$field}: (.+)$/mi", $prompt, $m)) return "{$field}: {$m[1]} (mock assistant).";
        return 'I do not have that information for this loan (mock assistant).';
    }
}
