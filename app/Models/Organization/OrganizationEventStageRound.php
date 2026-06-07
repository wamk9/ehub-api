<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationEventStageRound extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'organization_event_stage_rounds';

    protected $fillable = [
        'organization_event_stage_id', 'name', 'config', 'round_order',
        'initialized', 'in_progress', 'finished', 'start_at',
    ];

    protected $casts = [
        'config' => 'array',
        'initialized' => 'boolean',
        'in_progress' => 'boolean',
        'finished' => 'boolean',
        'start_at' => 'datetime',
    ];

    public function stage()
    {
        return $this->belongsTo(OrganizationEventStage::class, 'organization_event_stage_id');
    }
}
