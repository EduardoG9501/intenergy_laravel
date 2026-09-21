<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjecucionObra extends Model
{
    protected $table = 'ejecucion_obra';
    protected $primaryKey = 'id_ejecucion_obra';
    public $timestamps = false;

    protected $fillable = [
        'id_orden',
        'fecha_solicitud',
        'feriado',
        'observacion',
        'id_estado_ejecucion_obra'
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_orden', 'id_orden');
    }

    public function estadoEjecucion()
    {
        return $this->belongsTo(EstadoEjecucionObra::class, 'id_estado_ejecucion_obra', 'id_estado_ejecucion_obra');
    }
}
