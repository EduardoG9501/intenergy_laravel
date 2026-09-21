<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiquidacionTrabajoImagen extends Model
{
    protected $table = 'liquidacion_trabajo_imagen';
    protected $primaryKey = 'id_liquidacion_trabajo_imagen';
    public $timestamps = false;

    protected $fillable = [
        'id_liquidacion_trabajo',
        'nombre',
        'ruta',
        'fecha_subida',
        'estado'
    ];
}
