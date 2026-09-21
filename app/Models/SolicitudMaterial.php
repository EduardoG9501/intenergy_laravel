<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudMaterial extends Model
{
    protected $table = 'solicitud_materiales';
    protected $primaryKey = 'id_solicitud_material';
    public $timestamps = false;

    protected $fillable = [
        'id_orden',
        'fecha_solicitud',
        'id_bodega_principal',
        'id_bodega_secundaria',
        'id_responsable_obra',
        'id_entrega_articulos',
        'id_recibe_articulos',
        'observacion',
        'id_estado_solicitud'
    ];

    public function orden()
    {
        return $this->belongsTo(OrdenTrabajo::class, 'id_orden', 'id_orden');
    }

    public function bodegaPrincipal()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega_principal', 'id_bodega');
    }

    public function bodegaSecundaria()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega_secundaria', 'id_bodega');
    }

    public function responsable()
    {
        return $this->belongsTo(Empleado::class, 'id_responsable_obra', 'id_empleados');
    }

    public function entrega()
    {
        return $this->belongsTo(Empleado::class, 'id_entrega_articulos', 'id_empleados');
    }

    public function recibe()
    {
        return $this->belongsTo(Empleado::class, 'id_recibe_articulos', 'id_empleados');
    }

    public function estadoSolicitud()
    {
        return $this->belongsTo(EstadoSolicitudMaterial::class, 'id_estado_solicitud', 'id_estado_solicitud');
    }
}
