<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class LoanRequest extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'amount', 'tenure_months', 'purpose'];
    protected $casts = ['amount' => 'decimal:2', 'approved_at' => 'datetime'];
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function repayments(): HasMany { return $this->hasMany(Repayment::class); }
    public function documents(): HasMany { return $this->hasMany(Document::class); }
}
