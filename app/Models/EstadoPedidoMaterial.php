<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoPedidoMaterial extends Model
{
    protected $table = 'estado_pedido_materiales';
    protected $primaryKey = 'id_estado_pedido_material';
    public $timestamps = false;

    protected $fillable = [
        'estado',
        'activo'
    ];
}
