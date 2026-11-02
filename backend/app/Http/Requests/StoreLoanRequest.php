<?php
namespace App\Http\Requests;
use App\Models\LoanRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('create', LoanRequest::class); }
    public function rules(): array {
        return [
            'amount' => 'required|numeric|min:10000|max:1000000',
            'tenure_months' => 'required|integer|min:6|max:60',
            'purpose' => 'required|string|max:255',
        ];
    }
    public function messages(): array {
        return ['amount.min' => 'Amount must be at least ₹10,000.', 'amount.max' => 'Amount cannot exceed ₹10,00,000.'];
    }
}
