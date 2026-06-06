<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PayPalMarketplaceService
{
    private string $clientId;
    private string $clientSecret;
    private string $bnCode;
    private string $baseUrl;
    private string $redirectUri;

    public function __construct()
    {
        $this->clientId     = config('services.paypal_marketplace.client_id');
        $this->clientSecret = config('services.paypal_marketplace.client_secret');
        $this->bnCode       = config('services.paypal_marketplace.bn_code', '');
        $this->redirectUri  = config('services.paypal_marketplace.redirect_uri');
        $this->baseUrl      = config('paypal.mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    private function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

        return $response->json('access_token');
    }

    public function createPartnerReferral(string $orgId): string
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->withHeaders(['PayPal-Partner-Attribution-Id' => $this->bnCode])
            ->post("{$this->baseUrl}/v2/customer/partner-referrals", [
                'tracking_id'        => $orgId,
                'operations'         => [['operation' => 'API_INTEGRATION', 'api_integration_preference' => ['rest_api_integration' => ['integration_method' => 'PAYPAL', 'integration_type' => 'THIRD_PARTY', 'third_party_details' => ['features' => ['PAYMENT', 'REFUND']]]]]],
                'partner_config_override' => ['return_url' => $this->redirectUri . '?orgId=' . $orgId],
                'legal_consents'     => [['type' => 'SHARE_DATA_CONSENT', 'granted' => true]],
            ]);

        foreach ($response->json('links', []) as $link) {
            if ($link['rel'] === 'action_url') return $link['href'];
        }

        throw new \RuntimeException('PayPal partner referral failed: ' . $response->body());
    }

    public function getMerchantCredentials(string $merchantId): array
    {
        $token = $this->getAccessToken();
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/v1/customer/partners/{$this->clientId}/merchant-integrations/{$merchantId}");

        return $response->json();
    }

    public function createOrder(
        string $merchantId,
        float  $amount,
        string $currency,
        float  $platformFee,
        string $registrationId,
        string $returnUrl,
        string $cancelUrl
    ): array {
        $token = $this->getAccessToken();

        $amountStr     = number_format($amount, 2, '.', '');
        $platformFeeStr = number_format($platformFee, 2, '.', '');
        $curr          = strtoupper($currency);

        $response = Http::withToken($token)
            ->withHeaders(['PayPal-Partner-Attribution-Id' => $this->bnCode])
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'reference_id'  => $registrationId,
                    'amount'        => ['currency_code' => $curr, 'value' => $amountStr],
                    'payee'         => ['merchant_id' => $merchantId],
                    'payment_instruction' => [
                        'disbursement_mode' => 'INSTANT',
                        'platform_fees'     => [['amount' => ['currency_code' => $curr, 'value' => $platformFeeStr]]],
                    ],
                ]],
                'application_context' => [
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                ],
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException('PayPal order creation failed: ' . $response->body());
        }

        return $response->json();
    }

    public function captureOrder(string $orderId): array
    {
        $token = $this->getAccessToken();
        $response = Http::withToken($token)
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture");

        return $response->json();
    }

    public function getOrder(string $orderId): array
    {
        $token = $this->getAccessToken();
        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/v2/checkout/orders/{$orderId}");

        return $response->json();
    }
}
