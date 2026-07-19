<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelRequest extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'employeeId',
        'employeeName',
        'destination',
        'purpose',
        'startDate',
        'endDate',
        'estimatedCost',
        'advanceAmount',
        'status',
        'expenses',
        'approvedBy',
    ];

    protected $casts = [
        'estimatedCost' => 'float',
        'advanceAmount' => 'float',
        'expenses' => 'array', // stores an array of {item: string, amount: number}
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeId', 'id');
    }
}
