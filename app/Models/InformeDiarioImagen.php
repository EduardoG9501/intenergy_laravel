<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformeDiarioImagen extends Model
{
    protected $table = 'informe_diario_imagens';
    protected $primaryKey = 'id_informe_diario_imagen';
    protected $guarded = [];
    
    public function informe()
    {
        return $this->belongsTo(InformeDiarioEjecucion::class, 'id_informe_diario_ejecucion', 'id_informe_diario_ejecucion');
    }
}
