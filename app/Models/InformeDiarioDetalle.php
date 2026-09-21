<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformeDiarioDetalle extends Model
{
    protected $table = 'informe_diario_detalles';
    protected $primaryKey = 'id_informe_diario_detalle';
    protected $guarded = [];
    
    public function informe()
    {
        return $this->belongsTo(InformeDiarioEjecucion::class, 'id_informe_diario_ejecucion', 'id_informe_diario_ejecucion');
    }
}
