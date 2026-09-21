<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Obra extends Model
{
    protected $table = 'obra';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'estado',
        'fecha_registro'
    ];
}
