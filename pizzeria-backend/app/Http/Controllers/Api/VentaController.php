<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\Pedido;
use App\Models\DetallePedido;
use App\Models\Producto;
class VentaController extends Controller
{
    /**
     * Crear una nueva venta (pedido)
     */
   public function crearVenta(Request $request)
{
    try {

        $pedido = DB::transaction(function () use ($request) {

            
            $nuevoPedido = new Pedido();
            $nuevoPedido->estado = 'nuevo';
            $nuevoPedido->id_caja = $request->id_caja;
            $nuevoPedido->turno = $request->id_turno;
            $nuevoPedido->total = $request->total;
            $nuevoPedido->tipo_venta = $request->tipo_venta;
            $nuevoPedido->pagado = true;
            $nuevoPedido->entregado = false;
            $nuevoPedido->fecha = $request->fecha;
            $nuevoPedido->direccion = $request -> lugar;
            $nuevoPedido->cliente = $request -> nombreCliente;
            $nuevoPedido->save();

            
            $pedidoId = $nuevoPedido->id_pedido;

            
            foreach ($request->productos as $prod) {
                $detalle = new DetallePedido();
                $detalle->id_pedido = $pedidoId;
                $detalle->precio = $prod['precio_venta'];
                $detalle->id_producto = $prod['id'];
                $detalle->cantidad = $prod['cantidad'] ?? 1; 
                $detalle->save();
            }

            return $nuevoPedido; 
        });

        
        return response()->json([
            'status' => 'success',
            'message' => 'Venta creada correctamente',
            'pedido' => $pedido,
        ], 201);

    } catch (\Exception $e) {

        
        return response()->json([
            'status' => 'error',
            'message' => 'Error al crear la venta',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    
    /**
     * Obtener todos los pedidos pendientes (para la pantalla de cocina)
     */
    public function obtenerPedidos()
{
     $pedidos = Pedido::with('detalle.producto')->get();

    return response()->json(['pedidos' => $pedidos]);
}

    /**
     * Actualizar el estado de un pedido
     */
    public function actualizarEstadoPedido(Request $request)
{
    try {
        $pedido = Pedido::findOrFail($request->id); 
        $pedido->estado = $request->estado;
        
        if($request->estado == 'completado'){
            $pedido->entregado = true;
        }
        
        $pedido->save();
        
        return response()->json([
            'success' => true,
            'mensaje' => 'Estado actualizado correctamente'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'mensaje' => 'Error al actualizar el pedido'
        ], 500);
    }
}

public function pedidosReporte(Request $request)
{
    $fechaInicial = $request->fechaInicial;
    $fechaFinal = $request->fechaFinal;     

   $query = DB::table('producto')
    ->join('detalle_pedido as dp', 'producto.id_producto', '=', 'dp.id_producto')
    ->join('pedido as ped', 'dp.id_pedido', '=', 'ped.id_pedido')
    ->select(
        'producto.id_producto',
        'producto.nombre',
        DB::raw('COUNT(dp.id_detalle) as veces_vendida'),
        DB::raw('SUM(dp.cantidad) as cantidad'),
        DB::raw('SUM(dp.cantidad * dp.precio) as venta_total')
    )
    ->whereBetween(DB::raw('DATE(ped.fecha)'), [$fechaInicial, $fechaFinal])
    ->where('producto.tipo', 'Pizza')
    ->groupBy('producto.id_producto', 'producto.nombre')
    ->get();
    return response()->json([
        'success' => true,
        'data' => $query
    ]);
}

}