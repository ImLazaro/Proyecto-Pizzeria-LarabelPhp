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
        $user = User::join('empleado', 'users.id_empleado', '=', 'empleado.id_empleado')
    ->join('rol', 'empleado.id_rol', '=', 'rol.id_rol')
    ->where('users.nombre', $request->nombre)
    ->first();
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

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'success' => true,
            'message' => 'Sesion cerrada exitosamente',
        ]);
    }
}