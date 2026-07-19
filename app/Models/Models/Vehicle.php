<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'plateNumber',
        'model',
        'driverName',
        'status',
        'fuelLogs',
        'trips',
    ];

    protected $casts = [
        'fuelLogs' => 'array', // array of {date, liters, cost, mileage}
        'trips' => 'array', // array of {date, route, purpose, miles}
    ];
}
