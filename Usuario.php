<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;

    protected $table = 'usuario';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'nombre', 'apellido_paterno', 'apellido_materno',
        'contrasena', 'salario', 'fecha_de_contrato', 'duracion_de_contrato', 'id_rol'
    ];
}
