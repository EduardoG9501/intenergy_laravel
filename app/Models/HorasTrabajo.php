<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorasTrabajo extends Model
{
    protected $table = 'horas_trabajo';
    protected $primaryKey = 'id_horas_trabajo';
    public $timestamps = false;

    protected $fillable = [
        'fecha',
        'id_bodega_principal',
        'id_bodega_secundaria',
        'id_responsable_obra',
        'observacion',
        'fecha_creacion',
        'estado'
    ];

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
}
