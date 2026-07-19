<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    // Use customized string-based ID format (e.g., EMP-001)
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'gender',
        'dob',
        'maritalStatus',
        'phone',
        'email',
        'address',
        'emergencyContact',
        'citizenshipNo',
        'passportNo',
        'joinDate',
        'probationMonths',
        'contractType',
        'department',
        'designation',
        'salaryBasic',
        'salaryAllowances',
        'salaryDeductions',
        'pan',
        'ssf',
        'cit',
        'taxInfo',
        'assignedAssets',
        'education',
        'experience',
        'dependents',
        'profilePicture',
        'documents',
        'allowancesList',
        'deductionsList',
        'lifecycleHistory',
    ];

    protected $casts = [
        'probationMonths' => 'integer',
        'salaryBasic' => 'float',
        'salaryAllowances' => 'float',
        'salaryDeductions' => 'float',
        'assignedAssets' => 'array',
        'documents' => 'array',
        'allowancesList' => 'array',
        'deductionsList' => 'array',
        'lifecycleHistory' => 'array',
    ];

    /**
     * Relationship with Asset Model.
     */
    public function assets()
    {
        return $this->hasMany(Asset::class, 'assignedTo', 'id');
    }

    /**
     * Relationship with Attendance Log.
     */
    public function attendanceLogs()
    {
        return $this->hasMany(AttendanceLog::class, 'employeeId', 'id');
    }

    /**
     * Relationship with Leave Requests.
     */
    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'employeeId', 'id');
    }

    /**
     * Relationship with WFH Requests.
     */
    public function wfhRequests()
    {
        return $this->hasMany(WfhRequest::class, 'employeeId', 'id');
    }

    /**
     * Relationship with Timesheets.
     */
    public function timesheets()
    {
        return $this->hasMany(Timesheet::class, 'employeeId', 'id');
    }

    /**
     * Relationship with Travel Requests.
     */
    public function travelRequests()
    {
        return $this->hasMany(TravelRequest::class, 'employeeId', 'id');
    }
}
