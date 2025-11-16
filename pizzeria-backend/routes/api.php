<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\turnoController;
use App\Http\Controllers\Api\InsumoController;

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