<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('loan'));
    }

    public function rules(): array
    {
        return ['question' => 'required|string|min:3|max:300'];
    }
}