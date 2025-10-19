<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorteDeProducto extends Model
{
    use HasFactory;

    protected $table = 'corte_de_producto';
    protected $primaryKey = 'id_corteproducto';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'fecha',   
        'cantidad_teorica',
        'cantidad_real',
        'id_producto'
    ];
}
