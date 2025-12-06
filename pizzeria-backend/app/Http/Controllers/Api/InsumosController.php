<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class InsumosController extends Controller
{
    public function getReporteCompras(Request $request)
{
    $reporte = DB::table('almacen')
        ->join('insumos', 'almacen.id_insumo', '=', 'insumos.id_insumo')
        ->select(
            'almacen.id_almacen as id',
            'insumos.nombre as producto',   
            'almacen.cantidad_comprada as cantidad',
            'almacen.costo as precio_unitario', 
            'insumos.unidad_de_medida as unidad',
            'almacen.fecha_de_compra'
        )
        ->orderBy('almacen.fecha_de_compra', 'desc')
        ->get();

    return response()->json($reporte);
}
}