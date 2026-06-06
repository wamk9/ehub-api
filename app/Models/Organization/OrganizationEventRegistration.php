<?php

namespace App\Models\Organization;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationEventRegistration extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'organization_event_registrations';

    protected $fillable = [
        'organization_event_id', 'user_id', 'team_id',
        'form_data', 'payment_status', 'confirmed_at',
        'gateway', 'gateway_payment_id', 'gateway_preference_id',
    ];

    protected $casts = [
        'form_data'    => 'array',
        'confirmed_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(OrganizationEvent::class, 'organization_event_id')->with('organization');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stageResults()
    {
        return $this->hasMany(OrganizationEventStageResult::class, 'registration_id');
    }
}
