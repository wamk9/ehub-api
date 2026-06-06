<?php

namespace App\Http\Controllers;

use App\Models\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'mail'     => 'required|email',
            'password' => 'required',
        ]);

        if ($validated->fails()) {
            return response()->json(['message' => 'validation error', 'errors' => $validated->errors()], 401);
        }

        $user = User::where('mail', $request->mail)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        return response()->json([
            'message' => 'User Logged In Successfully',
            'token'   => $user->createToken('API TOKEN')->plainTextToken,
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'User successfully logged out'], 200);
    }
}
