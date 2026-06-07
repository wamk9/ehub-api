<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use Ramsey\Uuid\Uuid;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuids;

    // Sanctum tokens are not incrementing integers
    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted()
    {
        static::creating(function ($token) {
            if (! $token->id) {
                $token->id = (string) Uuid::uuid4();
            }
        });
    }
}
