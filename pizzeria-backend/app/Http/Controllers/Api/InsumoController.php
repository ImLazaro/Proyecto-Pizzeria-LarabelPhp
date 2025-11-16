<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Insumo;

class InsumoController extends Controller
{
    public function getInsumos()
    {
        $insumos = Insumo::all();
        return response()->json(['insumos' => $insumos]);
    }

    public function updateInsumo(Request $request)
    {   
        if(!($request -> id)){
            $insumo = new Insumo();
            $insumo->nombre = $request->nombre;
            $insumo->unidad_de_medida = $request->unidad;
            $insumo->cantidad_en_almacen = $request->cantidad;
            $insumo->costo= $request->costo;
            $insumo->save();
        } else {
            $insumo = Insumo::find($request->id);
            if(!$insumo){
                return response()->json(['message' => 'Insumo no encontrado'], 404);
            }
            $insumo->nombre = $request->nombre;
            $insumo->unidad_de_medida = $request->unidad;
            $insumo->cantidad_en_almacen = $request->cantidad;
            $insumo->costo= $request->costo;
            $insumo->save();
        }
        return response()->json(['message' => 'Insumo guardado exitosamente', 'success' => true]);
    }
}