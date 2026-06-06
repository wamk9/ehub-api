<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationEventStage extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'organization_event_stages';

    protected $fillable = [
        'organization_event_id', 'name', 'description',
        'stage_type', 'config', 'stage_order',
        'initialized', 'in_progress', 'finished', 'start_at',
    ];

    protected $casts = [
        'config'      => 'array',
        'initialized' => 'boolean',
        'in_progress' => 'boolean',
        'finished'    => 'boolean',
        'start_at'    => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(OrganizationEvent::class, 'organization_event_id');
    }

    public function rounds()
    {
        return $this->hasMany(OrganizationEventStageRound::class, 'organization_event_stage_id')
            ->orderBy('round_order');
    }

    public function results()
    {
        return $this->hasMany(OrganizationEventStageResult::class, 'organization_event_stage_id')
            ->orderBy('position');
    }
}
