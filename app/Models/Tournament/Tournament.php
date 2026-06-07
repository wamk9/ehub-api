<?php

namespace App\Models\Tournament;

use App\Models\Category\SubCategory;
use App\Models\EHub\Runmode;
use App\Models\League\League;
use App\Models\Payment\Currency;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tournament extends Model
{
    public $timestamps = false;

    protected $table = 'tournaments';

    protected $fillable = [
        'name',
        'description',
        'route',
        'price',
        'subscription_limit',
        'logo_image',
        'subcategory_id',
        'currency_id',
        'league_id',
        'runmode_id',
    ];

    protected $hidden = [
        'subcategory_id',
        'league_id',
        'currency_id',
        'runmode_id',
    ];

    use HasFactory, HasUuids;

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'subcategory_id');
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class, 'league_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function runmode(): BelongsTo
    {
        return $this->belongsTo(Runmode::class, 'runmode_id');
    }
}
