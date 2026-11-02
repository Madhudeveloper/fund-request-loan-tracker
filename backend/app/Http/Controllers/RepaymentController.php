<?php
namespace App\Http\Controllers;
use App\Http\Requests\StoreRepaymentRequest;
use App\Http\Resources\RepaymentResource;
use App\Models\LoanRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepaymentController extends Controller
{
    public function store(StoreRepaymentRequest $request, LoanRequest $loan): JsonResponse {
        $repayment = DB::transaction(function () use ($request, $loan) {
            $loan = LoanRequest::lockForUpdate()->findOrFail($loan->id); // serialise concurrent payments
            if ($loan->status !== 'approved') {
                throw ValidationException::withMessages(['loan' => 'Repayments can only be recorded on approved loans.']);
            }
            $repaid = (float) $loan->repayments()->sum('amount');
            $total = round($repaid + (float) $request->amount, 2);
            if ($total > (float) $loan->amount) {
                $left = number_format((float) $loan->amount - $repaid, 2);
                throw ValidationException::withMessages(['amount' => "Repayment exceeds the outstanding balance (₹{$left})."]);
            }
            $payment = $loan->repayments()->create($request->validated());
            
            if ($total >= round((float) $loan->amount, 2)) {
                $loan->status = 'closed';
                $loan->save();
            }
            return $payment;
        });
        return RepaymentResource::make($repayment)->response()->setStatusCode(201);
    }
}
