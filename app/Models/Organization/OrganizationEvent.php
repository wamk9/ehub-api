<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationEvent extends Model
{
    protected $table = 'organization_events';

    protected $fillable = [
        'organization_id', 'name', 'route', 'short_description', 'description',
        'image', 'fee', 'currency', 'max_registrations',
        'initialized', 'finished', 'start_at', 'category', 'runmode', 'form_schema_id',
    ];

    protected $casts = [
        'initialized'  => 'boolean',
        'finished'     => 'boolean',
        'fee'          => 'decimal:2',
        'start_at'     => 'datetime',
    ];

    use HasFactory, HasUuids;

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
