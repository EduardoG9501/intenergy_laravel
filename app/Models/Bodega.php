<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bodega extends Model
{
    protected $table = 'bodegas';
    protected $primaryKey = 'id_bodega';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'nombreBodega',
        'identificador',
        'es_bodega_secundaria',
        'id_bodega_principal',
        'fechaCaptura',
        'estado',
    ];

    public function principal()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega_principal', 'id_bodega');
    }

    public function secundarias()
    {
        return $this->hasMany(Bodega::class, 'id_bodega_principal', 'id_bodega');
    }
}
