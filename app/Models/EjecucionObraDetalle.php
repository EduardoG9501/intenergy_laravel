<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjecucionObraDetalle extends Model
{
    protected $table = 'ejecucion_obra_detalle_materiales_utilizar';
    protected $primaryKey = 'id_ejecucion_obra_detalle_materiales_utilizar';
    public $timestamps = false;

    protected $fillable = [
        'id_ejecucion_obra',
        'id_producto',
        'cantidad',
        'id_bodega_lugar',
        'Contabilizado',
        'Estado'
    ];

    public function producto()
    {
        return $this->belongsTo(Articulo::class, 'id_producto', 'id_producto');
    }

    public function bodegaLugar()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega_lugar', 'id_bodega');
    }
}
