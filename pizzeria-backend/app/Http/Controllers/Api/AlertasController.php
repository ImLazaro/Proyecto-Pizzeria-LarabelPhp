<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Alertas;
use Exception;
class AlertasController extends Controller{
    public function guardarAlerta (Request $request){
        try{
        
        DB::beginTransaction();
        DB::table('alertas')->insert([
            'id_usuario' =>$request['user_id'],
            'id_insumo' => $request['id_insumo'],
            'cantidad_alerta' => $request['cantidad_en_almacen'],
            'atendido' => false
        ]);
        DB::commit();
        return response()->json([
                'success' => true,
                'message' => 'Alerta registrada exitosamente'
            ], 200);
    
    } catch (Exception $e) {
            
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la alerta',
                'error' => $e->getMessage()
            ], 500);
        }
}

    function getAlertas(){
        $alertas = Alertas::with('insumo')-> where('atendido', '=',false)->get();
        return response () -> json (['success' => true, 'alertas' => $alertas]);
    }

    function marcarAlertas(Request $request){
        try{    
                $alerta= Alertas::findOrFail($request->id);
                $alerta->atendido = true;
                $alerta->save();
                return response()->json([
            'success' => true,
            'mensaje' => 'Estado actualizado correctamente'
        ]);
        }catch (Exception $e) {
            
            

            return response()->json([
                'success' => false,
                'message' => 'Error al modificar la alerta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}