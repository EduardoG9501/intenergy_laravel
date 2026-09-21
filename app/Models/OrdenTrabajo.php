<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenTrabajo extends Model
{
    protected $table = 'ordenes_trabajo';
    protected $primaryKey = 'id_orden';
    public $timestamps = false;

    protected $fillable = [
        'identificador',
        'id_proyecto',
        'id_obra',
        'costo',
        'fecha_inicial',
        'fecha_final',
        'id_bodega_principal',
        'id_bodega_secundaria',
        'observacion',
        'id_estado_orden',
        'fecha_graba',
        'estado'
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto', 'id');
    }

    public function obra()
    {
        return $this->belongsTo(Obra::class, 'id_obra', 'id');
    }

    public function bodegaPrincipal()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega_principal', 'id_bodega');
    }

    public function bodegaSecundaria()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega_secundaria', 'id_bodega');
    }

    public function estadoOrden()
    {
        return $this->belongsTo(EstadoOrden::class, 'id_estado_orden', 'id_estado_orden');
    }
}
