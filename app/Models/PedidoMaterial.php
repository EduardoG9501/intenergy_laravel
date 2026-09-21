<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoMaterial extends Model
{
    protected $table = 'pedido_materiales';
    protected $primaryKey = 'id_pedido_material';
    public $timestamps = false;

    protected $fillable = [
        'id_orden',
        'fecha_solicitud',
        'observacion',
        'id_estado_pedido_material'
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_orden', 'id_orden');
    }

    public function estadoPedido()
    {
        return $this->belongsTo(EstadoPedidoMaterial::class, 'id_estado_pedido_material', 'id_estado_pedido_material');
    }
}
