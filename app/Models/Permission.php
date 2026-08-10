<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Permission extends Model
{
    protected $fillable = [
        'uuid',
        'code',
        'name',
        'module',
        'description',
    ];

    protected static function booted(): void
    {
        static::creating(function (Permission $permission) {
            $permission->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions'
        )->withTimestamps();
    }
}