<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proyecto extends Model
{
    protected $table = 'proyecto';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'estado',
        'fecha_registro'
    ];

    public function obras()
    {
        return $this->hasMany(Obra::class, 'id_proyecto');
    }
}
