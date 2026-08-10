<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuthOtp extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'channel',
        'identifier',
        'purpose',
        'code_hash',
        'expires_at',
        'verified_at',
        'attempts',
        'reset_token_hash',
        'reset_token_expires_at',
    ];

    protected $hidden = [
        'code_hash',
        'reset_token_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'reset_token_expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AuthOtp $otp) {
            $otp->uuid ??= (string) Str::uuid();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}