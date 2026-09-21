<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermisoBodega extends Model
{
    protected $table = 'permiso_bodega';
    protected $primaryKey = 'id_permiso_bodega';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'id_bodega',
        'fecha_creacion',
        'estado',
    ];

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega', 'id_bodega');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
