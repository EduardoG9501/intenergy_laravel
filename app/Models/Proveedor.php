<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'proveedor';
    protected $primaryKey = 'id_proveedor';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'tipo_identificacion',
        'numero_identificacion',
        'razon_social',
        'direccion',
        'telefono',
        'email',
        'nombre_comercial',
        'tipo_contribuyente',
        'agente_retencion',
        'estado',
    ];
}
