<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Empleado;
use Illuminate\Support\Facades\DB;

class EmpleadoController extends Controller
{
    //
    function getUsers()
    {
        $users = DB::table('empleado as u')
            ->join('rol as r', 'u.id_rol', '=', 'r.id_rol')
            ->select('u.id_empleado','u.nombre','u.apellido_paterno', 'u.apellido_materno','u.fecha_de_contrato','r.nombre_rol as rol_nombre','r.id_rol as rol_id')
            ->get();

        return response()->json(['users' => $users]);
    }

    public function updateEmpleado(Request $request)
{
    
    $empleado = Empleado::updateOrCreate(
        ['id_empleado' => $request->id_empleado], 
        [
            'nombre' => $request->nombre,
            'apellido_paterno' => $request->apellido_paterno,
            'apellido_materno' => $request->apellido_materno,
            'fecha_de_contrato' => $request->fecha_de_contrato,
            'id_rol' => $request->rol_id,
            
        ]
    );

    $message = $request->id_empleado ? 'Empleado actualizado exitosamente' : 'Empleado guardado exitosamente';

    return response()->json([
        'message' => $message,
        'success' => true,
        'empleado' => $empleado
    ]);
}

}