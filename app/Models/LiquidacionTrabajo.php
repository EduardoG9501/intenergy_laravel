<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiquidacionTrabajo extends Model
{
    protected $table = 'liquidacion_trabajo';
    protected $primaryKey = 'id_liquidacion_trabajo';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_orden',
        'id_proyecto',
        'id_obra',
        'id_bodega',
        'fecha_inicial',
        'fecha_final',
        'mano_obra',
        'importante',
        'fecha_creacion',
        'fecha_actualizacion',
        'estado'
    ];

    public function imagenes()
    {
        return $this->hasMany(LiquidacionTrabajoImagen::class, 'id_liquidacion_trabajo');
    }
}
