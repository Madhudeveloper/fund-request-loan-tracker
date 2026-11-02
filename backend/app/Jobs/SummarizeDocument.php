<?php

namespace App\Jobs;

use App\Models\Document;
use App\Services\AI\LlmClient;
use App\Services\AI\LlmUnavailableException;
use App\Services\AI\Prompts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Smalot\PdfParser\Parser;

class SummarizeDocument implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Document $document)
    {
    }

    public function handle(LlmClient $llm): void
    {
        try {
            $text = (new Parser)->parseContent(Storage::get($this->document->file_path))->getText();
        } catch (\Throwable $e) {
            Log::warning('pdf.extract_failed', ['document' => $this->document->id, 'error' => $e->getMessage()]);
            $text = '';
        }

        // Hard cap on the text sent to the model (see AI_MAX_CHARS)
        $text = mb_substr(trim(preg_replace('/\s+/', ' ', $text)), 0, config('ai.max_chars'));
        if ($text === '') {
            $this->markFailed();
            return;
        }

        // Validate the JSON; if invalid, retry once, then mark failed
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $res = $llm->complete('summary', Prompts::summarySystem(), Prompts::summaryUser($text));
            } catch (LlmUnavailableException) {
                $this->markFailed();
                return;
            }

            $data = $this->valid($res->text);
            if ($data !== null) {
                $this->document->update(['summary' => $data, 'summary_status' => 'completed']);
                return;
            }
        }

        $this->markFailed();
    }

    /** Called by Laravel if the job crashes, so the page shows "failed" instead of staying pending. */
    public function failed(\Throwable $e): void
    {
        Log::error('summary.job_failed', ['document' => $this->document->id, 'error' => $e->getMessage()]);
        $this->markFailed();
    }

    private function valid(string $raw): ?array
    {
        $data = json_decode(trim(preg_replace('/^```(?:json)?|```$/m', '', $raw)), true);
        if (! is_array($data)) {
            return null;
        }

        $v = Validator::make($data, [
            'applicant_name' => 'present|nullable|string|max:255',
            'monthly_income' => 'present|nullable|numeric|min:0',
            'employer' => 'present|nullable|string|max:255',
            'key_risks' => 'present|array',
            'key_risks.*' => 'string|max:255',
            'summary' => 'required|string',
        ]);

        if ($v->fails() || str_word_count($data['summary']) > 80) {
            return null;
        }

        return $v->validated();
    }

    private function markFailed(): void
    {
        $this->document->update(['summary_status' => 'failed']);
    }
}