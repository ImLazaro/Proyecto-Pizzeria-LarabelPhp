<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CajaController extends Controller
{
    //
    function getCajas(){
        $cajas = DB::table('caja')
            ->get();

        return response()->json(['cajas' => $cajas]);
    }
}