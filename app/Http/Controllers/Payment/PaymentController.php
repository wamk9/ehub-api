<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment\Currency;
use App\Models\Payment\PaymentStatus;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function updateManuallyStatus(Request $request)
    {
        if (! $this->verifyLeagueUserAuthorization($request->user('sanctum')->id, $request->route('leagueRoute'), 'payment', 'change_payment_status')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }

    public function showAvailableStatus()
    {
        $paymentStatus = PaymentStatus::all();

        return response()->json(['message' => $paymentStatus], 200);
    }

    public function showAvailableCurrencies()
    {
        $currencies = Currency::all();

        return response()->json(['message' => $currencies], 200);
    }
}
