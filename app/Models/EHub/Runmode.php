<?php

namespace App\Models\EHub;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Runmode extends Model
{
    public $timestamps = false;

    protected $table = 'runmodes';

    protected $fillable = ['key'];

    use HasUuids;
}
