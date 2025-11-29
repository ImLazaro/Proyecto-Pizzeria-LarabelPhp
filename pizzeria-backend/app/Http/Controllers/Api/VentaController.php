<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use \PDOException;

// <-- NUEVO: Importamos los modelos que necesitamos
use App\Models\Producto;
use App\Models\Insumo;

use App\Models\Pedido;
use App\Models\DetallePedido;
class VentaController extends Controller
{
    /**
     * Crear una nueva venta (pedido)
     */
    public function crearVenta(Request $request)
    {

try {
    
    $pedidoData = DB::transaction(function () use ($request) {
        
        Log::info('=== INICIO CREAR VENTA ===');
        Log::info('Datos recibidos:', $request->all());

        // Validación de datos
        $validated = $request->validate([
            'nombreCliente' => 'required|string|max:255',
            'metodoPago' => 'required|string|max:50',
            'total' => 'required|numeric|min:0',
            'tipo_venta' => 'required|string|max:50',
            'id_turno' => 'required|integer',
            'id_caja' => 'required|integer',
            'productos' => 'required|array|min:1',
            'productos.*.id' => 'required|integer',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_venta' => 'required|numeric',
            'lugar' => 'nullable|string|max:255',
        ]);

        // 1. Agrupar productos (por si piden múltiples del mismo)
        $productosAgregados = [];
        foreach ($request->productos as $producto) {
            $id = $producto['id'];
            $cantidad = $producto['cantidad'];
            if (!isset($productosAgregados[$id])) {
                $productosAgregados[$id] = [
                    'cantidad' => 0,
                    'precio_venta' => $producto['precio_venta'],
                    'id' => $id
                ];
            }
            $productosAgregados[$id]['cantidad'] += $cantidad;
        }

        // 2. VERIFICACIÓN DE STOCK COMPLETA - SEGUNDA BARRERA
        Log::info('=== INICIANDO VERIFICACIÓN DE STOCK ===');
        
        $productosParaDeducir = [];
        $erroresStock = [];
        
        foreach ($productosAgregados as $productoId => $datosVenta) {
            
            $cantidadVendida = $datosVenta['cantidad'];
            
            // Obtener producto con su receta e insumos
            $producto = Producto::with('receta.insumo')->find($productoId);
            
            // Si no existe el producto
            if (!$producto) {
                throw ValidationException::withMessages([
                    'producto' => "El producto con ID {$productoId} no existe."
                ]);
            }
            
            // Si no tiene receta (refrescos, extras), no necesita verificación
            if ($producto->receta->isEmpty()) {
                Log::info("Producto '{$producto->nombre}' no requiere insumos.");
                continue;
            }
            
            $productosParaDeducir[] = [
                'producto' => $producto,
                'cantidad_vendida' => $cantidadVendida
            ];

            // Verificar CADA ingrediente de la receta
            foreach ($producto->receta as $ingrediente) {
                $insumo = $ingrediente->insumo;
                $cantidadRequerida = $ingrediente->cantidad * $cantidadVendida;
                
                Log::info("Verificando: '{$insumo->nombre}' para '{$producto->nombre}'");
                Log::info("- Stock actual: {$insumo->cantidad_en_almacen}");
                Log::info("- Cantidad requerida: {$cantidadRequerida}");

                // CRÍTICO: Verificar disponibilidad
                if ($insumo->cantidad_en_almacen < $cantidadRequerida) {
                    $faltante = $cantidadRequerida - $insumo->cantidad_en_almacen;
                    $erroresStock[] = "• {$producto->nombre}: Faltan {$faltante} {$insumo->unidad_de_medida} de '{$insumo->nombre}' (disponible: {$insumo->cantidad_en_almacen}, requerido: {$cantidadRequerida})";
                    
                    Log::warning("STOCK INSUFICIENTE: {$insumo->nombre}");
                }
            }
        }

        // Si hay errores de stock, rechazar la venta
        if (!empty($erroresStock)) {
            Log::error('VENTA RECHAZADA POR STOCK INSUFICIENTE');
            throw ValidationException::withMessages([
                'stock' => 'No hay suficientes ingredientes para completar la venta:||' . implode('||', $erroresStock)
            ]);
        }

        Log::info('✓ Verificación de stock exitosa. Procediendo con la venta...');

        // 3. Crear el pedido
        $pedidoId = DB::table('pedido')->insertGetId(
            [
                'id_caja'        => (int) $request->id_caja,
                'total'          => round((float) $request->total, 2),
                'pagado'         => true,
                'entregado'      => false,
                'turno'          => (int) $request->id_turno,
                'estado'         => 'Nuevo',
                'cliente'        => trim($request->nombreCliente),
                'metodo_pago'    => trim($request->metodoPago),
                'tipo_venta'     => trim($request->tipo_venta),
                'direccion'      => $request->lugar ?? '',
                'fecha'          => now()
            ],
            'id_pedido'
        );
        
        Log::info('✓ Pedido creado con ID: ' . $pedidoId);

        // 4. Insertar detalles del pedido
        $detallesParaInsertar = [];
        $itemsArray = [];

        foreach ($productosAgregados as $productoId => $datosVenta) {
            
            $productoDB = Producto::find($productoId);
            
            $detallesParaInsertar[] = [
                'id_pedido'    => $pedidoId,
                'id_producto'  => $productoId,
                'precio'       => round((float) $datosVenta['precio_venta'], 2),
                'cantidad'     => (int) $datosVenta['cantidad']
            ];
            
            $itemsArray[] = $productoDB->nombre . ' x' . $datosVenta['cantidad'];
        }

        if (!empty($detallesParaInsertar)) {
            DB::table('detalle_pedido')->insert($detallesParaInsertar);
            Log::info('✓ Detalles insertados: ' . count($detallesParaInsertar) . ' items');
        }
        
        // 5. DEDUCIR STOCK DEL ALMACÉN
        Log::info('=== INICIANDO DEDUCCIÓN DE STOCK ===');
        
        foreach ($productosParaDeducir as $item) {
            $producto = $item['producto'];
            $cantidadVendida = $item['cantidad_vendida'];
            
            foreach ($producto->receta as $ingrediente) {
                $cantidadADeducir = $ingrediente->cantidad * $cantidadVendida;
                
                // Deducción atómica del stock
                $ingrediente->insumo->decrement('cantidad_en_almacen', $cantidadADeducir);
                
                Log::info("✓ Deducido: {$cantidadADeducir} {$ingrediente->insumo->unidad_de_medida} de '{$ingrediente->insumo->nombre}'");
            }
        }
        
        Log::info('=== VENTA COMPLETADA EXITOSAMENTE ===');
        
        return ['id' => $pedidoId, 'items' => $itemsArray];
        
    }); // Fin de DB::transaction

    // Respuesta exitosa
    return response()->json([
        'success' => true,
        'message' => 'Venta registrada exitosamente',
        'orden_id' => $pedidoData['id'],
        'nueva_orden' => [
            'id' => $pedidoData['id'],
            'cliente' => $request->nombreCliente,
            'items' => $pedidoData['items'],
            'estado' => 'Nuevo',
            'total' => $request->total,
            'tipo_venta' => $request->tipo_venta,
            'metodo_pago' => $request->metodoPago,
        ],
    ], 201);
    
} catch (ValidationException $e) {
    
    Log::error('Error de validación:', $e->errors());
    
    // Manejo especial para errores de stock
    if (isset($e->errors()['stock'])) {
        $mensajeStock = $e->errors()['stock'][0];
        // Dividir el mensaje por el separador ||
        $partes = explode('||', $mensajeStock);
        $mensajePrincipal = $partes[0];
        $detalles = array_slice($partes, 1);
        
        return response()->json([
            'success' => false,
            'message' => $mensajePrincipal,
            'detalles' => $detalles,
            'tipo_error' => 'stock_insuficiente'
        ], 422);
    }
    
    return response()->json([
        'success' => false,
        'message' => 'Error de validación',
        'errors' => $e->errors(),
    ], 422);
    
} catch (PDOException $e) {
    
    Log::error('Error de base de datos: ' . $e->getMessage());
    Log::error('SQL State: ' . $e->getCode());
    
    return response()->json([
        'success' => false,
        'message' => 'Error en la base de datos',
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
    ], 500);
    
} catch (\Exception $e) {
    
    Log::error('Error general al crear venta: ' . $e->getMessage());
    Log::error('Línea: ' . $e->getLine());
    
    return response()->json([
        'success' => false,
        'message' => 'Error al crear la venta',
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
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
    public function actualizarEstadoPedido($id, Request $request)
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

   public function getVentas(Request $request)
{
    $fechaInicial = $request->fecha_inicial;
    $fechaFinal   = $request->fecha_final;

    if (!$fechaInicial || !$fechaFinal) {
        return response()->json([
            'message' => 'Debes enviar fecha_inicial y fecha_final'
        ], 400);
    }

    $ventas = Pedido::with('detalle.producto')
        ->whereBetween('fecha', [$fechaInicial . " 00:00:00", $fechaFinal . " 23:59:59"])
        ->orderBy('fecha', 'desc')
        ->get();

    return response()->json($ventas);
}

}