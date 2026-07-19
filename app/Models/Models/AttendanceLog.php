<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'employeeId',
        'employeeName',
        'date',
        'checkIn',
        'checkOut',
        'status',
        'overtimeMinutes',
        'lateMinutes',
    ];

    protected $casts = [
        'overtimeMinutes' => 'integer',
        'lateMinutes' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeId', 'id');
    }
}
