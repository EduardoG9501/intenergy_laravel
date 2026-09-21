<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoDetalleTmp extends Model
{
    protected $table = 'movimientos_detalle_tmp';
    protected $primaryKey = 'id_movimientos_detalle_tmp';
    public $timestamps = false;

    protected $fillable = [
        'id_movimiento',
        'id_articulo',
        'id_bodega_lugar',
        'precio',
        'cantidad',
        'lote',
        'bonificacion',
        'subtotal',
        'decuento',
        'iva',
        'total',
        'iva_porc',
        'desc_porc',
    ];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'id_articulo', 'id_producto');
    }

    public function bodegaLugar()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega_lugar', 'id_bodega');
    }
}
