<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationEventStageResult extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'organization_event_stage_results';

    protected $fillable = [
        'organization_event_stage_id', 'registration_id',
        'position', 'score', 'qualified', 'result_data',
    ];

    protected $casts = [
        'qualified'   => 'boolean',
        'result_data' => 'array',
        'score'       => 'decimal:4',
    ];

    public function stage()
    {
        return $this->belongsTo(OrganizationEventStage::class, 'organization_event_stage_id');
    }

    public function registration()
    {
        return $this->belongsTo(OrganizationEventRegistration::class, 'registration_id');
    }
}
