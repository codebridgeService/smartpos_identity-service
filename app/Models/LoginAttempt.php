<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoginAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'user_id',
        'identifier',
        'ip_address',
        'user_agent',
        'status',
        'failure_reason',
        'attempted_at',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (LoginAttempt $attempt) {
            $attempt->uuid ??= (string) Str::uuid();
        });
    }
}