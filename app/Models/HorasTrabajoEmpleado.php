<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorasTrabajoEmpleado extends Model
{
    protected $table = 'horas_trabajo_empleado';
    protected $primaryKey = 'id_horas_trabajo_empleado';
    public $timestamps = false;

    protected $fillable = [
        'id_horas_trabajo',
        'id_empleado',
        'numero_horas',
        'costo_por_hora',
        'total',
        'activo'
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleados');
    }
}
