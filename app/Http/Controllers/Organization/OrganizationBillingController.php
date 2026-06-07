<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization\Organization;
use App\Models\Organization\OrganizationBillingInvoice;
use App\Models\Organization\OrganizationBillingItem;
use App\Models\Organization\OrganizationMember;
use App\Services\NotificationService;
use App\Services\StripeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrganizationBillingController extends Controller
{
    private function canViewBilling(string $orgId, string $userId): bool
    {
        $member = OrganizationMember::where('organization_id', $orgId)
            ->where('user_id', $userId)
            ->first();

        return $member && in_array($member->role, ['owner', 'admin', 'financial']);
    }

    public function index(Request $request)
    {
        $org = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        if (! $this->canViewBilling($org->id, $request->user('sanctum')->id)) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $invoices = OrganizationBillingInvoice::where('organization_id', $org->id)
            ->orderBy('billing_cycle', 'desc')
            ->limit(24)
            ->get()
            ->map(fn ($inv) => $this->formatInvoice($inv));

        // Current month unbilled items
        $currentCycle = Carbon::now()->format('Y-m');
        $pendingItems = OrganizationBillingItem::where('organization_id', $org->id)
            ->whereNull('billing_cycle')
            ->count();

        $pendingTotal = OrganizationBillingItem::where('organization_id', $org->id)
            ->whereNull('billing_cycle')
            ->sum('fee_amount');

        return response()->json([
            'message' => [
                'invoices' => $invoices,
                'current_cycle' => $currentCycle,
                'pending_items' => $pendingItems,
                'pending_total' => (float) $pendingTotal,
                'billing_blocked' => (bool) $org->billing_blocked_at,
                'has_card' => (bool) $org->stripe_customer_id,
            ],
        ], 200);
    }

    public function setupStripe(Request $request)
    {
        $org = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        if (! $this->canViewBilling($org->id, $request->user('sanctum')->id)) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $stripe = app(StripeService::class);
        $customerId = $stripe->createOrGetCustomer($org);
        $secret = $stripe->createSetupIntent($customerId);

        return response()->json(['message' => ['client_secret' => $secret]], 200);
    }

    public function confirmStripeCard(Request $request)
    {
        $org = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        if (! $this->canViewBilling($org->id, $request->user('sanctum')->id)) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $paymentMethodId = $request->input('payment_method_id');
        if (! $paymentMethodId) {
            return response()->json(['message' => 'payment_method_id_required'], 422);
        }

        app(StripeService::class)->setDefaultPaymentMethod(
            $org->stripe_customer_id,
            $paymentMethodId
        );

        NotificationService::sendToOrgRoles(
            $org->id,
            ['owner', 'admin', 'financial'],
            'notification.billing_card_saved',
            ['org' => $org->name],
            '/org/'.$org->route.'/manage'
        );

        return response()->json(['message' => 'card_saved'], 200);
    }

    public function invoiceDetails(Request $request)
    {
        $org = Organization::where('route', $request->route('orgRoute'))->first();
        if (! $org) {
            return response()->json(['message' => 'org_not_found'], 404);
        }

        if (! $this->canViewBilling($org->id, $request->user('sanctum')->id)) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $invoice = OrganizationBillingInvoice::where('organization_id', $org->id)
            ->where('billing_cycle', $request->route('cycle'))
            ->with('items.registration.user:id,name,username')
            ->first();

        if (! $invoice) {
            return response()->json(['message' => 'invoice_not_found'], 404);
        }

        return response()->json([
            'message' => $this->formatInvoice($invoice, true),
        ], 200);
    }

    private function formatInvoice(OrganizationBillingInvoice $inv, bool $withItems = false): array
    {
        $data = [
            'id' => $inv->id,
            'billing_cycle' => $inv->billing_cycle,
            'total_amount' => (float) $inv->total_amount,
            'status' => $inv->status,
            'due_date' => $inv->due_date?->format('Y-m-d'),
            'paid_at' => $inv->paid_at,
            'failed_at' => $inv->failed_at,
        ];

        if ($withItems) {
            $data['items'] = $inv->items->map(fn ($item) => [
                'id' => $item->id,
                'billing_type' => $item->billing_type,
                'fee_amount' => (float) $item->fee_amount,
                'user' => $item->registration?->user ? [
                    'name' => $item->registration->user->name,
                    'username' => $item->registration->user->username,
                ] : null,
            ])->values();
        }

        return $data;
    }
}
