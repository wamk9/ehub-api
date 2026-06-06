<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailVerificationController extends Controller
{
    private const TTL      = 600;   // 10 minutes
    private const MAX_TRIES = 5;

    public function sendCode(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'mail' => 'required|email',
        ]);

        if ($validated->fails()) {
            return response()->json(['message' => 'E-mail inválido.'], 422);
        }

        $mail = strtolower($request->mail);
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put("ev:{$mail}", ['code' => $code, 'tries' => 0], self::TTL);

        Mail::to($mail)->send(new EmailVerificationCode($code));

        return response()->json(['message' => 'Code sent'], 200);
    }

    public function verifyCode(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'mail' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validated->fails()) {
            return response()->json(['message' => 'Dados inválidos.'], 422);
        }

        $mail  = strtolower($request->mail);
        $entry = Cache::get("ev:{$mail}");

        if (!$entry) {
            return response()->json(['message' => 'Código expirado ou não solicitado.'], 422);
        }

        if ($entry['tries'] >= self::MAX_TRIES) {
            Cache::forget("ev:{$mail}");
            return response()->json(['message' => 'Muitas tentativas. Solicite um novo código.'], 429);
        }

        if ($entry['code'] !== $request->code) {
            $entry['tries']++;
            Cache::put("ev:{$mail}", $entry, self::TTL);
            return response()->json(['message' => 'Código incorreto.'], 422);
        }

        Cache::forget("ev:{$mail}");
        Cache::put("ev_verified:{$mail}", true, 300); // 5 min to complete registration
        return response()->json(['message' => 'E-mail verificado.'], 200);
    }
}
