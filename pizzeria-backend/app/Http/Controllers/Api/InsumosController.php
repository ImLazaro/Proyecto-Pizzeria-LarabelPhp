<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class InsumosController extends Controller
{
    //
    function getInsumos()
    {
        $insumos = DB::table('insumos')
            ->orderBy('id_insumo', 'desc')
            ->get();

        return response()->json(['insumos' => $insumos]);
    }
}