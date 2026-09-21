<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoSolicitudMaterial extends Model
{
    protected $table = 'estado_solicitud_materiales';
    protected $primaryKey = 'id_estado_solicitud';
    public $timestamps = false;

    protected $fillable = [
        'estado',
        'activo'
    ];
}
