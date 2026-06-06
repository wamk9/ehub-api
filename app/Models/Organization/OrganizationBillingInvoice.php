<?php

namespace App\Models\Organization;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class OrganizationBillingInvoice extends Model
{
    use HasUuids;

    protected $table = 'organization_billing_invoices';

    protected $fillable = [
        'organization_id', 'billing_cycle', 'total_amount',
        'stripe_invoice_id', 'stripe_payment_intent_id',
        'status', 'due_date', 'paid_at', 'failed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'due_date'     => 'date',
        'paid_at'      => 'datetime',
        'failed_at'    => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function items()
    {
        return $this->hasMany(OrganizationBillingItem::class, 'invoice_id');
    }
}
