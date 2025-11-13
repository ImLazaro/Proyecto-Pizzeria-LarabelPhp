<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('nombre', $request->nombre)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
            'success' => false,
            'message' => 'Credenciales invalidas',
            ]);
        }

        $token = $user->createToken('AccessToken')->accessToken;

        return response()->json([
            'token' => $token,
            'success' => true,
            'user' => $user
        ]);
    }
}