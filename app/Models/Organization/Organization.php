<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// Relations used: OrganizationPaymentGateway, OrganizationBillingInvoice (lazy-loaded)

class Organization extends Model
{
    protected $table = 'organizations';

    protected $fillable = [
        'name', 'route', 'description', 'logo_image',
        'about', 'instagram', 'facebook', 'x_twitter', 'website', 'phone', 'contact_email',
        'stripe_customer_id', 'billing_blocked_at',
    ];

    protected $casts = [
        'billing_blocked_at' => 'datetime',
    ];

    use HasFactory, HasUuids;

    public function members()
    {
        return $this->hasMany(OrganizationMember::class);
    }

    public function events()
    {
        return $this->hasMany(OrganizationEvent::class);
    }

    public function paymentGateways()
    {
        return $this->hasMany(OrganizationPaymentGateway::class);
    }

    public function billingInvoices()
    {
        return $this->hasMany(OrganizationBillingInvoice::class);
    }

    public function isBillingBlocked(): bool
    {
        return (bool) $this->billing_blocked_at;
    }
}
