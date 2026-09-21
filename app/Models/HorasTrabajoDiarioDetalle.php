<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorasTrabajoDiarioDetalle extends Model
{
    protected $table = 'horas_trabajo_diario_detalle';
    protected $primaryKey = 'id_horas_trabajo_diario_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_ejecucion_obra',
        'id_empleado',
        'hora_entrada',
        'hora_salida',
        'cantidad_horas_normal',
        'cantidad_horas_extra',
        'cantidad_horas_extraordinaria',
        'fecha_registro'
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleados');
    }
}
