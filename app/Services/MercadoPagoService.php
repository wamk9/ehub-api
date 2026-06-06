<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MercadoPagoService
{
    private string $clientId;

    private string $clientSecret;

    private string $redirectUri;

    private string $baseUrl;

    public function __construct()
    {
        $this->clientId = config('services.mercadopago.client_id');
        $this->clientSecret = config('services.mercadopago.client_secret');
        $this->redirectUri = config('services.mercadopago.redirect_uri');
        $this->baseUrl = config('services.mercadopago.sandbox', false)
            ? 'https://api.mercadopago.com'
            : 'https://api.mercadopago.com';
    }

    public function getOAuthUrl(string $state): string
    {
        return 'https://auth.mercadopago.com/authorization?'.http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'platform_id' => 'mp',
            'redirect_uri' => $this->redirectUri,
            'state' => $state,
        ]);
    }

    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->post('https://api.mercadopago.com/oauth/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('MP OAuth exchange failed: '.$response->body());
        }

        return $response->json();
    }

    public function refreshToken(string $refreshToken): array
    {
        $response = Http::asForm()->post('https://api.mercadopago.com/oauth/token', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('MP token refresh failed: '.$response->body());
        }

        return $response->json();
    }

    public function createPreference(
        string $accessToken,
        string $title,
        float $amount,
        string $currency,
        string $registrationId,
        string $successUrl,
        string $failureUrl,
        string $pendingUrl,
        float $marketplaceFee = 0.0
    ): array {
        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/checkout/preferences", [
                'items' => [[
                    'title' => $title,
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'currency_id' => strtoupper($currency),
                ]],
                'marketplace' => $this->clientId,
                'marketplace_fee' => round($marketplaceFee, 2),
                'external_reference' => $registrationId,
                'back_urls' => [
                    'success' => $successUrl,
                    'failure' => $failureUrl,
                    'pending' => $pendingUrl,
                ],
                'auto_return' => 'approved',
                'notification_url' => config('app.url').'/api/payment/webhook/mercadopago',
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException('MP preference creation failed: '.$response->body());
        }

        return $response->json();
    }

    public function searchPaymentsByExternalReference(string $accessToken, string $externalRef): array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/v1/payments/search", [
                'external_reference' => $externalRef,
                'sort' => 'date_created',
                'criteria' => 'desc',
            ]);

        if (! $response->successful()) {
            return ['results' => []];
        }

        return $response->json();
    }

    public function getPayment(string $accessToken, string $paymentId): array
    {
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/v1/payments/{$paymentId}");

        if (! $response->successful()) {
            throw new \RuntimeException('MP get payment failed: '.$response->body());
        }

        return $response->json();
    }
}
