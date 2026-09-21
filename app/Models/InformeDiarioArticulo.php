<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformeDiarioArticulo extends Model
{
    protected $table = 'informe_diario_articulos';
    protected $primaryKey = 'id_informe_diario_articulo';
    protected $guarded = [];
    
    public function informe()
    {
        return $this->belongsTo(InformeDiarioEjecucion::class, 'id_informe_diario_ejecucion', 'id_informe_diario_ejecucion');
    }
    
    public function producto()
    {
        return $this->belongsTo(Articulo::class, 'id_producto', 'id_producto');
    }
}
