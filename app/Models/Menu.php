<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    protected $table = 'menu';
    protected $primaryKey = 'id_menu';
    public $timestamps = false;

    protected $fillable = [
        'menu_padre',
        'menu_hijo',
        'icono_padre',
        'icono_hijo',
        'descripcion',
        'opcion',
        'fecha_creacion',
        'estado',
    ];
}
