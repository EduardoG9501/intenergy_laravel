<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    protected $table = 'movimientos';
    protected $primaryKey = 'id_movimiento';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_bodega',
        'id_tipo_movimiento',
        'id_sub_tipo_movimiento',
        'id_proveedor',
        'no_documento',
        'fechaDocumento',
        'id_forma_pago',
        'fechaPago',
        'cant_dia_pago',
        'fechaCaptura',
        'observacion',
        'estado',
        'guardarDefinitivo',
        'iva_mto',
        'desc_mto',
        'subtotal_mto',
        'total_mto',
        'iva_porc',
        'desc_porc',
    ];

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega', 'id_bodega');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }

    public function tipoMovimiento()
    {
        return $this->belongsTo(TipoMovimiento::class, 'id_tipo_movimiento', 'id_tipo_movimiento');
    }

    public function subTipoMovimiento()
    {
        return $this->belongsTo(SubTipoMovimiento::class, 'id_sub_tipo_movimiento', 'id_sub_tipo_movimiento');
    }
}
