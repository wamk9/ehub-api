<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OrganizationPaymentGateway extends Model
{
    use HasUuids;

    protected $table = 'organization_payment_gateways';

    protected $fillable = [
        'organization_id', 'gateway', 'access_token', 'refresh_token',
        'gateway_user_id', 'public_key', 'expires_at', 'active',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    protected $casts = [
        'active'     => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
