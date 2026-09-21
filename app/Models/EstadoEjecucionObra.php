<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoEjecucionObra extends Model
{
    protected $table = 'estado_ejecucion_obra';
    protected $primaryKey = 'id_estado_ejecucion_obra';
    public $timestamps = false;

    protected $fillable = [
        'estado',
        'activo'
    ];
}
