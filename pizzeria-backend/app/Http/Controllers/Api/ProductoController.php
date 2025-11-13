<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Producto;
use App\Models\Receta;
class ProductoController extends Controller
{
    public function getPizzas()
    {
        $pizzas = DB::table('producto as p')
            ->select('p.id_producto', 'p.nombre', 'p.precio', 'p.estado', 'p.tipo', 
                'r.cantidad', 'i.nombre as insumo', 'i.unidad_de_medida','r.id_receta','i.id_insumo')
            ->join('receta as r', 'p.id_producto', '=', 'r.id_pizza')
            ->join('insumos as i', 'r.id_insumo', '=', 'i.id_insumo')
            ->where('p.tipo', 'Pizza')
            ->get();


            $grouped = $pizzas->groupBy('id_producto')->map(function($items) {
             $pizza = $items->first();
            return [
                'id_producto' => $pizza->id_producto,
                'nombre' => $pizza->nombre,
                'precio' => $pizza->precio,
                'estado' => $pizza->estado,
                'tipo' => $pizza->tipo,
                'receta' => $items->map(function($item) {
                return [
                    'nombre' => $item->insumo,
                    'cantidad' => $item->cantidad,
                    'unidad_de_medida' => $item->unidad_de_medida,
                    'id_receta' => $item->id_receta,
                    'id_insumo' => $item->id_insumo,
            ]   ;
            })->values()
    ];
        })->values();

        return response()->json(['pizzas' => $grouped]);

    }

    public function updatePizza(Request $request)
{   
    if(!($request -> id)){
        $pizza = new Producto();
        $pizza->nombre = $request->nombre;
        $pizza->precio = $request->costo;
        $pizza->estado = true;
        $pizza->tipo = 'Pizza';
        $pizza->save();
        $pizzaId = DB::table('producto')
    ->where('tipo', 'Pizza')
    ->latest('id_producto')
    ->first()
    ->id_producto;

        foreach ($request->receta as $recetaData) {
            $receta = new \App\Models\Receta();
            $receta->id_insumo = $recetaData['id_insumo'];
            $receta->cantidad = $recetaData['cantidad'];
            $receta->id_pizza = $pizzaId;
            $pizza->receta()->save($receta);
        }
        
    }
    $pizza = Producto::find($request->id);
    if (!$pizza) {
        return response()->json(['message' => 'Pizza not found'], 404);
    }
    $pizza->nombre = $request->nombre;
    $pizza->precio = $request->costo;   
    $pizza->save();
    foreach ($request->receta as $recetaData) {
        $receta = Receta::find($recetaData['id_receta']);
        if ($receta) {
            $receta->cantidad = $recetaData['cantidad'];
            $receta->id_insumo = $recetaData['id_insumo'];
            $receta->save();
        }
    }



return response()->json(['message' => 'Pizza updated successfully', 'pizza' => $pizza->load('receta')]);


}

public function getProductos()
{
    $productos = DB::table('producto')
    ->select('id_producto', 'nombre', 'precio', 'estado', 'tipo')
    ->where('estado', true)
    ->get();

    $grouped = $productos->groupBy('tipo')->mapWithKeys(fn($items, $key) => [
    $key => $items->values()
]);

    return response()->json(['productos' => $grouped]);
}
}