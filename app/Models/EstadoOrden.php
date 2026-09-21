<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoOrden extends Model
{
    protected $table = 'estado_ordenes';
    protected $primaryKey = 'id_estado_orden';
    public $timestamps = false;

    protected $fillable = [
        'estado',
        'activo'
    ];
}
