<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CargoEmpleado extends Model
{
    protected $table = 'cargo_empleado';
    protected $primaryKey = 'id_cargo';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'cargo',
        'fechaCaptura',
        'estado',
    ];
}
