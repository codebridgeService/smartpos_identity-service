<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserDevice extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'device_uuid',
        'device_name',
        'device_type',
        'platform',
        'first_ip_address',
        'last_ip_address',
        'is_trusted',
        'is_blocked',
        'last_seen_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'is_blocked' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (UserDevice $device) {
            $device->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sessions()
    {
        return $this->hasMany(
            UserSession::class
        );
    }
}