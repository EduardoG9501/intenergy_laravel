<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformeDiarioEjecucion extends Model
{
    protected $table = 'informe_diario_ejecucions';
    protected $primaryKey = 'id_informe_diario_ejecucion';
    protected $guarded = [];
    
    public $timestamps = true;
    
    public function ejecucion()
    {
        return $this->belongsTo(EjecucionObra::class, 'id_ejecucion_obra', 'id_ejecucion_obra');
    }
    
    public function empleados()
    {
        return $this->hasMany(InformeDiarioEmpleado::class, 'id_informe_diario_ejecucion', 'id_informe_diario_ejecucion');
    }
    
    public function articulos()
    {
        return $this->hasMany(InformeDiarioArticulo::class, 'id_informe_diario_ejecucion', 'id_informe_diario_ejecucion');
    }
    
    public function imagenes()
    {
        return $this->hasMany(InformeDiarioImagen::class, 'id_informe_diario_ejecucion', 'id_informe_diario_ejecucion');
    }
    
    public function detalles()
    {
        return $this->hasMany(InformeDiarioDetalle::class, 'id_informe_diario_ejecucion', 'id_informe_diario_ejecucion');
    }
}

