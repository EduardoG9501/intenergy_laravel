<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermisoUsuarioMenu extends Model
{
    protected $table = 'permisos_usuario_menu';
    protected $primaryKey = 'id_permisos_usuario_menu';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_menu',
        'fecha_creacion',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'id_menu', 'id_menu');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
