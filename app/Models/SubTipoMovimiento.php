<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubTipoMovimiento extends Model
{
    protected $table = 'sub_tipo_movimiento';
    protected $primaryKey = 'id_sub_tipo_movimiento';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'sub_tipo',
        'fechaCaptura',
        'id_tipo_movimiento',
        'estado',
    ];

    public function tipoMovimiento()
    {
        return $this->belongsTo(TipoMovimiento::class, 'id_tipo_movimiento', 'id_tipo_movimiento');
    }
}
