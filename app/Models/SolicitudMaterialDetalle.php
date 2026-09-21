<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudMaterialDetalle extends Model
{
    protected $table = 'solicitud_materiales_detalle';
    protected $primaryKey = 'id_solicitud_material_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_solicitud_material',
        'id_producto',
        'cantidad',
        'id_detalle_articulo'
    ];

    public function producto()
    {
        return $this->belongsTo(Articulo::class, 'id_producto', 'id_producto');
    }
}
