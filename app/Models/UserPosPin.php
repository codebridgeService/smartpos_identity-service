<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserPosPin extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'business_uuid',
        'pin_hash',
        'is_active',
        'failed_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'pin_hash',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'locked_until' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (UserPosPin $model) {
            $model->uuid ??= (string) Str::uuid();
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
}