<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanStatusRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('decide', $this->route('loan')); }
    public function rules(): array {
        return ['status' => 'required|in:approved,rejected', 'remarks' => 'required|string|max:500'];
    }
}
