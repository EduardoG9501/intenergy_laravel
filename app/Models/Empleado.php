<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';
    protected $primaryKey = 'id_empleados';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'nombres_apellidos',
        'cedula',
        'id_genero',
        'direccion',
        'email',
        'telefono',
        'fecha_nacimiento',
        'id_cargo',
        'estado',
    ];

    public function cargo()
    {
        return $this->belongsTo(CargoEmpleado::class, 'id_cargo', 'id_cargo');
    }
}
