<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class RepaymentResource extends JsonResource
{
    public function toArray($request): array {
        return ['id' => $this->id, 'amount' => (float) $this->amount, 'paid_on' => $this->paid_on->toDateString(), 'reference_no' => $this->reference_no];
    }
}
