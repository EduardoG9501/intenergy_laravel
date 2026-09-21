<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformeDiarioEmpleado extends Model
{
    protected $table = 'informe_diario_empleados';
    protected $primaryKey = 'id_informe_diario_empleado';
    protected $guarded = [];
    
    public function informe()
    {
        return $this->belongsTo(InformeDiarioEjecucion::class, 'id_informe_diario_ejecucion', 'id_informe_diario_ejecucion');
    }
    
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'id_empleado', 'id_empleados');
    }
}
