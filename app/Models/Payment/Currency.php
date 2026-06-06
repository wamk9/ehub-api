<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    public $timestamps = false;

    protected $table = 'currencies';

    protected $fillable = ['name', 'iso_code', 'symbol'];

    use HasFactory, HasUuids;
}
