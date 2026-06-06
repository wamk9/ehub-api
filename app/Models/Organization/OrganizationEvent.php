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
        'logo_image', 'cover_image', 'fee', 'currency', 'max_registrations',
        'initialized', 'finished', 'start_at',
        'category', 'subcategory', 'runmode', 'form_schema_id',
        'event_data', 'registration_form_template',
    ];

    protected $casts = [
        'initialized' => 'boolean',
        'finished' => 'boolean',
        'fee' => 'decimal:2',
        'start_at' => 'datetime',
        'event_data' => 'array',
        'registration_form_template' => 'array',
    ];

    use HasFactory, HasUuids;

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
