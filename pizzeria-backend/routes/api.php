<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\turnoController;
use App\Http\Controllers\Api\InsumoController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\EmpleadoController;
use App\Http\Controllers\Api\VentaController;

Route::post('login', [AuthController::class, 'login']);

Route::get('get-pizzas', [ProductoController::class, 'getPizzas']);
Route::get('get-insumos', [App\Http\Controllers\Api\InsumosController::class, 'getInsumos']);
Route::post('update-pizza', [ProductoController::class, 'updatePizza']);
Route::get('get-menu', [ProductoController::class, 'getProductos']);
Route::get('get-cajas', [CajaController::class, 'getCajas']);
Route::get('get-ultimo-turno', [turnoController::class, 'getUltimoTurno']);
Route::post('crear-turno', [turnoController::class, 'crearTurno']);
Route::get('get-insumos', [InsumoController::class, 'getInsumos']);
Route::post('update-insumo', [InsumoController::class, 'updateInsumo']);
Route::get('get-users', [EmpleadoController::class, 'getUsers']);
Route::post('update-empleado', [EmpleadoController::class, 'updateEmpleado']);
Route::post('save-user', [UserController::class, 'saveUser']);
Route::post('buscar-user', [UserController::class,'buscarUsuario']);

Route::post(uri: '/crear-venta', action: [VentaController::class, 'crearVenta']);
Route::get(uri: '/obtener-pedidos', action: [VentaController::class, 'obtenerPedidos']);
Route::post(uri: '/actualizar-estado-pedido/{id}', action: [VentaController::class, 'actualizarEstadoPedido']);
Route::post('get-pizzas-reporte', [VentaController::class,'pedidosReporte']);