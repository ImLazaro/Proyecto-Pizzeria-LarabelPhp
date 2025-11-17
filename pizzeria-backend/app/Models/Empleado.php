<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model{
   protected $table = 'empleado';


    protected $primaryKey = 'id_empleado';

    
    public $incrementing = true;

    
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'salario',
        'fecha_de_contrato',
        'duracion_de_contrato',
        'id_rol'
    ];

}