<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray($request): array {
        $repaid = (float) ($this->repayments_sum_amount ?? 0); // loaded with withSum()/loadSum(), so no N+1
        return [
            'id' => $this->id,
            'user' => UserResource::make($this->whenLoaded('user')),
            'amount' => (float) $this->amount,
            'tenure_months' => $this->tenure_months,
            'purpose' => $this->purpose,
            'status' => $this->status,
            'remarks' => $this->remarks,
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
            'repaid' => $repaid,
            'outstanding' => (float) $this->amount - $repaid,
            'repayments' => RepaymentResource::collection($this->whenLoaded('repayments')),
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
        ];
    }
}
