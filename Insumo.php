<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    use HasFactory;

    protected $table = 'insumos';
    protected $primaryKey = 'id_insumo';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'cantidad_en_almacen',
        'unidad_de_medida'
    ];
}
