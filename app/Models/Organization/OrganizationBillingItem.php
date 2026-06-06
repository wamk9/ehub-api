<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OrganizationBillingItem extends Model
{
    use HasUuids;

    protected $table = 'organization_billing_items';

    protected $fillable = [
        'organization_id', 'registration_id',
        'billing_type', 'fee_amount', 'billing_cycle', 'invoice_id',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function registration()
    {
        return $this->belongsTo(OrganizationEventRegistration::class, 'registration_id');
    }

    public function invoice()
    {
        return $this->belongsTo(OrganizationBillingInvoice::class, 'invoice_id');
    }
}
