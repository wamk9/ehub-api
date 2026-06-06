<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasUuids;

    const UPDATED_AT = null;

    protected $table = 'notifications';

    protected $hidden = [
        'user_id',
    ];

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'route',
        'read_at',
    ];

    use HasFactory;
}
