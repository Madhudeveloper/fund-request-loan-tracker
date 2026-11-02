<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreRepaymentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('repay', $this->route('loan')); }
    public function rules(): array {
        return [
            'amount' => 'required|numeric|min:1',
            'paid_on' => 'required|date|before_or_equal:today',
            'reference_no' => 'required|string|max:100|unique:repayments,reference_no',
        ];
    }
}
