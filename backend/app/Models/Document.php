<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = ['loan_request_id', 'file_path', 'original_name', 'summary', 'summary_status'];
    protected $casts = ['summary' => 'array'];
    public function loan(): BelongsTo { return $this->belongsTo(LoanRequest::class, 'loan_request_id'); }
}
