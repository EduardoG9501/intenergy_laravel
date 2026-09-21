<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoMovimiento extends Model
{
    protected $table = 'tipo_movimiento';
    protected $primaryKey = 'id_tipo_movimiento';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'tipo',
        'fechaCaptura',
        'estado',
        'entrada',
        'salida',
    ];
}
