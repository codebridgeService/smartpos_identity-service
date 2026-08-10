<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Role extends Model
{
    protected $fillable = [
        'uuid',
        'business_uuid',
        'name',
        'code',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Role $role) {
            $role->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'user_roles'
        )->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions'
        )->withTimestamps();
    }
}