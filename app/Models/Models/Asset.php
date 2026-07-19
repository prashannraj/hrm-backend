<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'code',
        'name',
        'category',
        'assignedTo',
        'purchaseDate',
        'cost',
        'status',
        'maintenanceLogs',
    ];

    protected $casts = [
        'cost' => 'float',
        'maintenanceLogs' => 'array', // stores array of {date, cost, description}
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'assignedTo', 'id');
    }
}
