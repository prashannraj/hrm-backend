<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'employeeId',
        'employeeName',
        'leaveType',
        'startDate',
        'endDate',
        'reason',
        'status',
        'approvedBy',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeId', 'id');
    }
}
