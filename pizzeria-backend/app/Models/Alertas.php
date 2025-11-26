<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alertas extends Model {
    use HasFactory;

    protected $table = 'alertas';
    protected $primaryKey = 'id';
    

    protected $fillable = [
        'id_usuario',
        'id_insumo',
        'cantidad_alerta',
        'atendido'
    ];
    public function insumo() {
        return $this->belongsTo(Insumo::class, 'id_insumo');
    }
}