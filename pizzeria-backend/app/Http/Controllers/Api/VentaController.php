<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use \PDOException;

class VentaController extends Controller
{
    /**
     * Crear una nueva venta (pedido)
     */
    public function crearVenta(Request $request)
    {
        // Encapsulamos toda la lógica en la transacción
        // Se hará commit automáticamente si no hay errores,
        // o rollback si ocurre cualquier excepción.
        try {
            
            $pedidoData = DB::transaction(function () use ($request) {
                
                Log::info('=== INICIO CREAR VENTA ===');
                Log::info('Datos recibidos:', $request->all());

                // Validación de datos requeridos
                $validated = $request->validate([
                    'nombreCliente' => 'required|string|max:255',
                    'metodoPago' => 'required|string|max:50',
                    'total' => 'required|numeric|min:0',
                    'tipo_venta' => 'required|string|max:50',
                    'id_turno' => 'required|integer',
                    'id_caja' => 'required|integer',
                    'productos' => 'required|array|min:1',
                    'productos.*.id' => 'required|integer', // Valida cada producto
                    'lugar' => 'nullable|string|max:255',
                ]);

                // --- ¡LA CORRECCIÓN ESTÁ AQUÍ! ---
                // Le decimos a insertGetId que la llave primaria es 'id_pedido'
                $pedidoId = DB::table('pedido')->insertGetId(
                    [
                        'id_caja'        => (int) $request->id_caja,
                        'total'          => round((float) $request->total, 2),
                        'pagado'         => true,
                        'entregado'      => false,
                        'turno'          => (int) $request->id_turno,
                        'estado'         => 'Nuevo',
                        'nombre_cliente' => trim($request->nombreCliente),
                        'metodo_pago'    => trim($request->metodoPago),
                        'tipo_venta'     => trim($request->tipo_venta),
                        'lugar'          => $request->lugar ?? '',
                        'fecha'          => now() // Aseguramos que la fecha se establezca
                    ],
                    'id_pedido' // <-- ¡ESTE ES EL ARREGLO!
                );
                
                Log::info('Pedido insertado exitosamente con ID: ' . $pedidoId);

                $itemsArray = [];
                $detallesParaInsertar = [];

                // Insertar cada producto en detalle_pedido
                foreach ($request->productos as $index => $producto) {
                    Log::info("Procesando producto {$index}:", $producto);
                    
                    $productoDB = DB::table('producto')
                        ->where('id_producto', $producto['id'])
                        ->first();
                    
                    if ($productoDB) {
                        $detallesParaInsertar[] = [
                            'id_pedido'    => $pedidoId,
                            'id_insumo'    => 1, // id_insumo por defecto
                            'id_proveedor' => 1, // id_proveedor por defecto
                            'precio'       => round((float) $producto['precio_venta'], 2),
                            'producto'     => $productoDB->nombre,
                            'cantidad'     => (int) $producto['cantidad']
                        ];
                        
                        $itemsArray[] = $productoDB->nombre . ' x' . $producto['cantidad'];
                        
                    } else {
                        Log::warning('Producto no encontrado con ID: ' . $producto['id']);
                        throw new \Exception("Producto no encontrado con ID: " . $producto['id']);
                    }
                }

                if (!empty($detallesParaInsertar)) {
                    DB::table('detalle_pedido')->insert($detallesParaInsertar);
                    Log::info('Detalles insertados en lote: ' . count($detallesParaInsertar));
                }
                
                Log::info('=== VENTA COMPLETADA EXITOSAMENTE ===');
                
                // Devolvemos el ID y los items para la respuesta
                return ['id' => $pedidoId, 'items' => $itemsArray];
                
            }); // Fin de DB::transaction

            // Si la transacción fue exitosa, preparamos la respuesta
            return response()->json([
                'success' => true,
                'message' => 'Venta registrada exitosamente',
                'orden_id' => $pedidoData['id'],
                'nueva_orden' => [
                    'id' => $pedidoData['id'],
                    'cliente' => $request->nombreCliente,
                    'items' => $pedidoData['items'], // Usamos los items generados
                    'estado' => 'Nuevo',
                    'total' => $request->total,
                    'tipo_venta' => $request->tipo_venta,
                    'metodo_pago' => $request->metodoPago,
                ],
            ], 201);
            
        } catch (ValidationException $e) {
            // ... (código de catch sin cambios) ...
// ... (Tu código para ValidationException está bien) ...
            Log::error('Error de validación:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (PDOException $e) {
            // ... (código de catch sin cambios) ...
// ... (Tu código para PDOException está bien) ...
            Log::error('Error de base de datos: ' . $e->getMessage());
            Log::error('SQL State: ' . $e->getCode());
            return response()->json([
                'success' => false,
                'message' => 'Error en la base de datos',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 500);
            
        } catch (\Exception $e) {
            // ... (código de catch sin cambios) ...
// ... (Tu código para Exception está bien) ...
            Log::error('Error general al crear venta: ' . $e->getMessage());
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
// ... (Tu código para obtenerPedidos() está bien, no se necesita cambiar) ...
// ... (Tu código para obtenerPedidos() está bien) ...
    }
    
    /**
     * Actualizar el estado de un pedido
     */
    public function actualizarEstadoPedido($id, Request $request)
    {
// ... (Tu código para actualizarEstadoPedido() está bien, no se necesita cambiar) ...
// ... (Tu código para actualizarEstadoPedido() está bien) ...
    }
}