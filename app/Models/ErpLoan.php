<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpLoan extends Model
{
    use HasFactory;

    protected $table = 'erp_loans';

    protected $fillable = [
        'company_id', 'loan_account_id', 'interest_account_id', 'loan_number', 'lender_name',
        'start_date', 'principal_amount', 'interest_rate', 'tenure_months', 'outstanding_principal', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'principal_amount' => 'float',
        'interest_rate' => 'float',
        'outstanding_principal' => 'float',
        'tenure_months' => 'integer',
    ];

    public function loanAccount(): BelongsTo { return $this->belongsTo(ErpAccount::class, 'loan_account_id'); }
    public function interestAccount(): BelongsTo { return $this->belongsTo(ErpAccount::class, 'interest_account_id'); }
    public function schedules(): HasMany { return $this->hasMany(ErpLoanRepaymentSchedule::class, 'loan_id'); }
}
