<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('upload', $this->route('loan')); }
    public function rules(): array { return ['file' => 'required|file|mimes:pdf|max:5120']; }
    public function messages(): array { return ['file.mimes' => 'Only PDF files are allowed.', 'file.max' => 'The PDF must not exceed 5 MB.']; }
}
