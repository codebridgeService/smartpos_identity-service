<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RolePermission extends Model
{
    protected $table = 'role_permissions';

    protected $fillable = [
        'role_id',
        'permission_id',
    ];



    public function role(): BelongsTo
    {
        return $this->belongsTo(
            Role::class,
            'role_id'
        );
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(
            Permission::class,
            'permission_id'
        );
    }
}