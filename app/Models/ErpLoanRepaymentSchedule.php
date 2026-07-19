<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpLoanRepaymentSchedule extends Model
{
    use HasFactory;

    protected $table = 'erp_loan_repayment_schedules';

    protected $fillable = ['loan_id', 'due_date', 'principal_due', 'interest_due', 'paid_amount', 'status'];
    protected $casts = ['due_date' => 'date', 'principal_due' => 'float', 'interest_due' => 'float', 'paid_amount' => 'float'];

    public function loan(): BelongsTo { return $this->belongsTo(ErpLoan::class, 'loan_id'); }
}
