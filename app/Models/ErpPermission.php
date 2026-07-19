<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ErpPermission extends Model
{
    use HasFactory;

    protected $table = 'erp_permissions';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'action',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(ErpRole::class, 'erp_role_permissions', 'permission_id', 'role_id');
    }
}