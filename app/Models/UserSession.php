<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserSession extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'user_device_id',
        'refresh_token_hash',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'refresh_token_hash',
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (UserSession $session) {
            $session->uuid ??= (string) Str::uuid();
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

    public function device()
    {
        return $this->belongsTo(
            UserDevice::class,
            'user_device_id'
        );
    }
}