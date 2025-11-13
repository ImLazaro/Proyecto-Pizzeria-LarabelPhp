<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductoController;

Route::post('login', [AuthController::class, 'login']);

Route::get('get-pizzas', [ProductoController::class, 'getPizzas']);
Route::get('get-insumos', [App\Http\Controllers\Api\InsumosController::class, 'getInsumos']);
Route::post('update-pizza', [ProductoController::class, 'updatePizza']);
Route::get('get-menu', [ProductoController::class, 'getProductos']);