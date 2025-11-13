<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Insumo;
class Receta extends Model {
    protected $table = 'receta';

    protected $fillable = ['id_insumo', 'cantidad','id_pizza'];
    protected $primaryKey = 'id_receta';
    public $timestamps = false;
    public function insumo() {
        return $this->belongsTo(Insumo::class, 'id_insumo');
    }
}