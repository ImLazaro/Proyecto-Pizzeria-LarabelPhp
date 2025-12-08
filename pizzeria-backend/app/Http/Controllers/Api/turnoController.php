<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Turno;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class turnoController extends Controller
{
    //
    function getUltimoTurno()
    {
        //
        $id=DB::table('turno')
            ->select('id_turno')
            ->latest('id_turno')
            ->first();

        return response()->json(['ultimo_turno' => $id]);
    }
    function crearTurno(Request $request)
    {
        //
        $turno = new Turno();
        $turno->fecha = $request->fecha;
        $turno->hora_de_entrada= $request->hora_inicio;
        $turno->id_user = $request->id_user;
        $turno->id_caja = $request->caja;
        $turno->fondo_inicial = $request->fondoIncial;
        $turno->save();
    }

    function terminarTurno(Request $request)
    {
        //
        $turno = Turno::find($request->id);
        $turno->hora_de_salida= $request->hora_de_salida;
        
        $turno->save();
    }
}