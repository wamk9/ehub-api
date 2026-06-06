<?php

namespace App\Models\League;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class League extends Model
{
    protected $table = 'leagues';

    protected $fillable = ['logo_image', 'name', 'route', 'description'];

    use HasFactory, HasUuids;
}
