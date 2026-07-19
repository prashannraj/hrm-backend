<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'acronym',
        'registeredAddress',
        'email',
        'phone',
        'registrationNo',
        'fiscalYear',
        'departments',
        'designations',
        'leavePolicies',
    ];

    protected $casts = [
        'departments' => 'array',
        'designations' => 'array',
        'leavePolicies' => 'array', // array of {type, allocation, cashable}
    ];
}
