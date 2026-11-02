<?php

namespace App\Http\Controllers;

use App\Http\Requests\AskRequest;
use App\Models\LoanRequest;
use App\Services\AI\LlmClient;
use App\Services\AI\LlmUnavailableException;
use App\Services\AI\Prompts;
use Illuminate\Http\JsonResponse;

class AskController extends Controller
{
    public function __invoke(AskRequest $request, LoanRequest $loan, LlmClient $llm): JsonResponse
    {
        try {
            $res = $llm->complete('ask', Prompts::askSystem(), Prompts::askUser($this->context($loan), $request->question));
        } catch (LlmUnavailableException) {
            return response()->json(['message' => 'The assistant is busy right now. Please try again in a minute.'], 503);
        }

        return response()->json(['answer' => trim($res->text)]);
    }

    /** Only this loan's own data is ever sent to the model. */
    private function context(LoanRequest $loan): string
    {
        $loan->load([
            'repayments' => fn ($q) => $q->latest('paid_on')->limit(20),
            'documents',
        ])->loadSum('repayments', 'amount');

        $repaid = (float) ($loan->repayments_sum_amount ?? 0);
        $lines = [
            "Loan ID: {$loan->id}",
            "Status: {$loan->status}",
            'Amount: Rs. ' . number_format($loan->amount, 2),
            "Tenure: {$loan->tenure_months} months",
            "Purpose: {$loan->purpose}",
            'Repaid: Rs. ' . number_format($repaid, 2),
            'Outstanding: Rs. ' . number_format($loan->amount - $repaid, 2),
            'Admin remarks: ' . ($loan->remarks ?: 'none'),
            'Repayments (latest 20):',
        ];

        foreach ($loan->repayments as $r) {
            $lines[] = "- {$r->paid_on->format('d-m-Y')}: Rs. " . number_format($r->amount, 2) . " ({$r->reference_no})";
        }
        foreach ($loan->documents as $d) {
            if ($d->summary) {
                $lines[] = "Document {$d->original_name}: " . ($d->summary['summary'] ?? '');
            }
        }

        return mb_substr(implode("\n", $lines), 0, 4000);
    }
}