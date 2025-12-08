<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CorteDeCaja;


class CortedeCajaController extends Controller
{
    //

    function crearCorteCaja(Request $request)
    {
        $corte = new CorteDeCaja();
        $corte->id_turno = $request->id_turno;
        $corte->id_user = $request->id_usuario;
        $corte->fecha = $request->fecha;
        $corte->fondo_inicial = $request->fondo_inicial;
        $corte->fondo_final = $request->fondo_final;
        $corte->save();

         return response()->json([
        'message' => 'Corte de caja creado correctamente',
        'corte' => $corte
    ]);
    }
}