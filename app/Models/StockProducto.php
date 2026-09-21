<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockProducto extends Model
{
    protected $table = 'stock_productos';
    protected $primaryKey = 'id_stock_productos';
    public $timestamps = false;

    protected $fillable = [
        'id_movimiento',
        'id_tipo_movimiento',
        'id_sub_tipo_movimiento',
        'no_documento',
        'id_bodega_principal',
        'id_bodega_secundaria',
        'tipo_movimiento',
        'sub_tipo_movimiento',
        'id_movimiento_detalle',
        'id_producto',
        'producto',
        'lote',
        'precio',
        'cantidad',
        'subtotal',
        'decuento',
        'iva',
        'total',
        'stock',
        'id_stock_credito',
        'estado',
        'id_usuario',
        'fecha_captura',
    ];
}
