<?php

namespace App\Services;

use Stripe\StripeClient;

class StripeConnectService
{
    private StripeClient $stripe;

    private string $clientId;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
        $this->clientId = config('services.stripe.connect_client_id');
    }

    public function getOAuthUrl(string $state): string
    {
        return 'https://connect.stripe.com/oauth/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'scope' => 'read_write',
            'state' => $state,
            'redirect_uri' => config('services.stripe.connect_redirect_uri'),
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $response = $this->stripe->oauth->token([
            'grant_type' => 'authorization_code',
            'code' => $code,
        ]);

        return (array) $response;
    }

    public function disconnect(string $connectedAccountId): void
    {
        $this->stripe->oauth->deauthorize([
            'client_id' => $this->clientId,
            'stripe_user_id' => $connectedAccountId,
        ]);
    }

    /**
     * Create a Stripe Checkout Session on the connected account.
     * application_fee_amount is collected automatically by the platform.
     */
    public function createCheckoutSession(
        string $connectedAccountId,
        string $eventName,
        int $amountCents,
        string $currency,
        int $feeCents,
        string $registrationId,
        string $successUrl,
        string $cancelUrl
    ): array {
        $session = $this->stripe->checkout->sessions->create(
            [
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => strtolower($currency),
                        'product_data' => ['name' => $eventName],
                        'unit_amount' => $amountCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'payment_intent_data' => [
                    'application_fee_amount' => $feeCents,
                    'metadata' => ['registration_id' => $registrationId],
                ],
                'client_reference_id' => $registrationId,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ],
            ['stripe_account' => $connectedAccountId]
        );

        return (array) $session;
    }

    public function getCheckoutSession(string $sessionId, string $connectedAccountId): array
    {
        $session = $this->stripe->checkout->sessions->retrieve(
            $sessionId,
            [],
            ['stripe_account' => $connectedAccountId]
        );

        return (array) $session;
    }
}
