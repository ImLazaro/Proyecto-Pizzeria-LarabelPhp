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

                // Validación de datos (igual que antes)
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

                // --- NUEVO: 1. Agregamos los productos ---
                // Agrupamos por si el cliente pide 3 pizzas iguales
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

                // --- NUEVO: 2. Verificamos el Stock ---
                $productosParaDeducir = []; // Guardamos los modelos para usarlos después
                
                foreach ($productosAgregados as $productoId => $datosVenta) {
                    
                    $cantidadVendida = $datosVenta['cantidad'];
                    
                    // Buscamos el producto CON su receta Y el insumo de cada ingrediente
                    $producto = Producto::with('receta.insumo')->find($productoId);
                    
                    // Si el producto no existe o no tiene receta (ej. un refresco), saltamos
                    if (!$producto || $producto->receta->isEmpty()) {
                        Log::info("Producto {$productoId} no tiene receta, saltando chequeo de stock.");
                        continue;
                    }
                    
                    $productosParaDeducir[] = $producto; // Guardamos para el paso 4

                    // Revisamos cada ingrediente de la receta
                    foreach ($producto->receta as $ingrediente) {
                        $insumo = $ingrediente->insumo;
                        $cantidadRequerida = $ingrediente->cantidad * $cantidadVendida;
                        
                        Log::info("Revisando insumo '{$insumo->nombre}' para '{$producto->nombre}'");
                        Log::info("Stock actual: {$insumo->cantidad_en_almacen}. Cantidad requerida: {$cantidadRequerida}");

                        if ($insumo->cantidad_en_almacen < $cantidadRequerida) {
                            // ¡No hay stock! Lanzamos un error de validación
                            throw ValidationException::withMessages([
                                'stock' => "Stock insuficiente: Se requieren {$cantidadRequerida} de '{$insumo->nombre}' para '{$producto->nombre}', pero solo hay {$insumo->cantidad_en_almacen}."
                            ]);
                        }
                    }
                }

                // --- 3. Creamos el Pedido (Tu código, con el fix de 'id_pedido') ---
                $pedidoId = DB::table('pedido')->insertGetId(
                    [
                        'id_caja'        => (int) $request->id_caja,
                        'total'          => round((float) $request->total, 2),
                        'pagado'         => true,
                        'entregado'      => false,
                        'turno'          => (int) $request->id_turno,
                        'estado'         => 'Nuevo',
                        'cliente'        => trim($request->nombreCliente), // <- CORREGIDO (era 'nombre_cliente')
                        'metodo_pago'    => trim($request->metodoPago),
                        'tipo_venta'     => trim($request->tipo_venta),
                        'direccion'      => $request->lugar ?? '', // <- CORREGIDO (era 'lugar')
                        'fecha'          => now()
                    ],
                    'id_pedido' // <-- Esto estaba bien
                );
                
                Log::info('Pedido insertado exitosamente con ID: ' . $pedidoId);

                // --- 4. Insertamos Detalles (Lógica corregida y refactorizada) ---
                $detallesParaInsertar = [];
                $itemsArray = []; // Para la respuesta JSON

                foreach ($productosAgregados as $productoId => $datosVenta) {
                    
                    $productoDB = Producto::find($productoId); // Ya sabemos que existe
                    
                    $detallesParaInsertar[] = [
                        'id_pedido'    => $pedidoId,
                        'id_producto'  => $productoId, // <- CORREGIDO
                        'precio'       => round((float) $datosVenta['precio_venta'], 2), // <- CORREGIDO
                        'cantidad'     => (int) $datosVenta['cantidad'] // <- CORREGIDO
                    ];
                    
                    $itemsArray[] = $productoDB->nombre . ' x' . $datosVenta['cantidad'];
                }

                if (!empty($detallesParaInsertar)) {
                    DB::table('detalle_pedido')->insert($detallesParaInsertar);
                    Log::info('Detalles insertados en lote: ' . count($detallesParaInsertar));
                }
                
                // --- NUEVO: 5. Deducimos el Stock ---
                // Esto solo se ejecuta si el chequeo de stock (paso 2) fue exitoso
                foreach ($productosParaDeducir as $producto) {
                    $cantidadVendida = $productosAgregados[$producto->id_producto]['cantidad'];
                    
                    foreach ($producto->receta as $ingrediente) {
                        $cantidadADeducir = $ingrediente->cantidad * $cantidadVendida;
                        
                        // Usamos decrement para una operación atómica
                        $ingrediente->insumo->decrement('cantidad_en_almacen', $cantidadADeducir);
                        Log::info("Stock deducido: {$cantidadADeducir} de '{$ingrediente->insumo->nombre}'");
                    }
                }
                
                Log::info('=== VENTA COMPLETADA EXITOSAMENTE ===');
                
                return ['id' => $pedidoId, 'items' => $itemsArray];
                
            }); // Fin de DB::transaction

            // Respuesta exitosa (igual que antes)
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
                    'metodo_pago' => $request->metodoPago, // <- CORREGIDO (era 'metodo_pago')
                ],
            ], 201);
            
        } catch (ValidationException $e) {
            // --- MODIFICADO: Manejamos el error de stock ---
            Log::error('Error de validación:', $e->errors());
            
            // Si el error es el que lanzamos por 'stock'
            if (isset($e->errors()['stock'])) {
                return response()->json([
                    'success' => false,
                    'message' => $e->errors()['stock'][0], // Enviamos el mensaje de stock
                    'errors' => $e->errors(),
                ], 422); // 422 es el código correcto
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
            
        } catch (PDOException $e) {
            // ... (sin cambios) ...
            Log::error('Error de base de datos: ' . $e->getMessage());
            Log::error('SQL State: ' . $e->getCode());
            return response()->json([
                'success' => false,
                'message' => 'Error en la base de datos',
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ], 500);
            
        } catch (\Exception $e) {
            // ... (sin cambios) ...
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
        // ... (Tu código aquí - sin cambios) ...
    }
    
    /**
     * Actualizar el estado de un pedido
     */
    public function actualizarEstadoPedido($id, Request $request)
    {
        // ... (Tu código aquí - sin cambios) ...
    }
}