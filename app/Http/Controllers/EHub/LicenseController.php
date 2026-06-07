<?php

namespace App\Http\Controllers\EHub;

use App\Http\Controllers\Controller;
use App\Models\EHub\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class LicenseController extends Controller
{
    public function adquireLicense(Request $request)
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Unauthorized', 'status' => false], 401);
        }

        $planId = $request->only(['plan'])['plan'];

        if (! $planId) {
            return response()->json(['message' => 'Plan id not setted', 'status' => false], 401);
        }

        $user = User::find(auth()->user()->id);
        $plan = Plan::find($planId);
        $plan['currency'] = $plan->currencies;
        $plan['description'] = $plan['name'].' (eHub)';

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        $provider->setCurrency($plan['currency']['currency_iso_code']);

        $response = $provider->addProduct($plan['name'], $plan['description'], 'SERVICE', 'SOFTWARE')
            ->addMonthlyPlan($plan['name'], $plan['description'], $plan['price'])
            ->setReturnAndCancelUrl(route('paypal.payment.successful'), route('paypal.payment.canceled'))
            ->setupSubscription($user->name, $user->email, now()->toDateString());

        if (isset($response['id']) && $response['id'] != null) {
            foreach ($response['links'] as $links) {
                if ($links['rel'] == 'approve') {
                    return redirect()->away($links['href']);
                }
            }

            return response()->json(['message' => 'Transaction canceled', 'status' => false], 401);
        }

        return response()->json(['message' => 'Something went wrong', 'status' => false], 401);
    }

    public function canceledLicensePayment()
    {
        return response()->json(['message' => 'You have canceled the transaction', 'status' => false], 401);
    }

    public function adquiredLicense(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($request['token']);

        if (isset($response['status']) && $response['status'] == 'COMPLETED') {
            return response()->json(['message' => 'Transaction completed.', 'status' => true], 200);
        }

        return response()->json(['message' => 'Something went wrong', 'status' => false], 401);
    }
}
