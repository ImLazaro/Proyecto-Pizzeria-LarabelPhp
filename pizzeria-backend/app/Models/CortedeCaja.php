<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorteDeCaja extends Model
{
    use HasFactory;

    protected $table = 'corte_de_caja';
    protected $primaryKey = 'id_corte';
    public $timestamps = false;

    protected $fillable = [
        'id_turno',
        'id_usuario',
        'fecha',
        'fondo_inicial',
        'fondo_final'
    ];
}
