<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    //

    function getUsers()
    {
        
    }
     

    function saveUser(Request $request)
    { 
        try{
        $user = new User();
        $user->nombre = $request->usuario;
        $user->password = Hash::make($request->password);   
        $user->id_empleado = $request->id_empleado;
        $user->save();
        return response()->json(['message' => 'Usuario guardado exitosamente', 'success' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al guardar el usuario', 'success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    function buscarUsuario(Request $request){
        $usuario= DB::table('users as u')
        ->select('e.nombre','e.apellido_materno','e.apellido_paterno')
        -> join('empleado as e', 'u.id_empleado', '=', 'e.id_empleado')
        ->first()
        ->get();

        return response()->json([$usuario]);
    }
}