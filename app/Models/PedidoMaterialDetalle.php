<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoMaterialDetalle extends Model
{
    protected $table = 'pedido_materiales_detalle';
    protected $primaryKey = 'id_pedido_material_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_pedido_material',
        'id_producto',
        'cantidad',
        'Estado'
    ];

    public function producto()
    {
        return $this->belongsTo(Articulo::class, 'id_producto', 'id_producto');
    }
}
