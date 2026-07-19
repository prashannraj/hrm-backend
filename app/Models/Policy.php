<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Policy extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'category',
        'version',
        'publishDate',
        'content',
        'acknowledgedBy',
    ];

    protected $casts = [
        'acknowledgedBy' => 'array', // array of employee IDs
    ];
}
