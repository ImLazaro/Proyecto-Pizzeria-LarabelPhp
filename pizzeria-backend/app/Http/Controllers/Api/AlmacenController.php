<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Exception;
class AlmacenController extends Controller {
    public function registrarCompra(Request $request)
    {
        try {
            // Validar los datos recibidos
            $validated = $request->validate([
                'user_id' => 'required|integer',
                'id_insumo' => 'required|integer',
                'cantidad' => 'required|numeric|min:0.01',
                'costoTotal' => 'required|numeric|min:0.01',
                'fecha' => 'required|string'
            ]);

            // Iniciar transacción
            DB::beginTransaction();

            // Guardar en la tabla almacen
            DB::table('almacen')->insert([
                'id_user' => $validated['user_id'],
                'id_insumo' => $validated['id_insumo'],
                'fecha_de_compra' => $validated['fecha'],
                'cantidad_comprada' => $validated['cantidad'],
                'costo' => $validated['costoTotal']
            ]);

            // Actualizar la cantidad en la tabla insumos
            DB::table('insumos')
                ->where('id_insumo', $validated['id_insumo'])
                ->increment('cantidad_en_almacen', $validated['cantidad']);

            // Confirmar transacción
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compra registrada exitosamente'
            ], 200);

        } catch (Exception $e) {
            // Revertir transacción en caso de error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}