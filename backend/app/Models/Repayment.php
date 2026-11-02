<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Repayment extends Model
{
    protected $fillable = ['loan_request_id', 'amount', 'paid_on', 'reference_no'];
    protected $casts = ['amount' => 'decimal:2', 'paid_on' => 'date'];
    public function loan(): BelongsTo { return $this->belongsTo(LoanRequest::class, 'loan_request_id'); }
}
