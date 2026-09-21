<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $primaryKey = 'id_categoria';
    public $timestamps = false;

    protected $fillable = [
        'id_usuario',
        'nombreCategoria',
        'fechaCaptura',
        'subcategoria',
        'estado',
    ];

    public function articulos()
    {
        return $this->hasMany(Articulo::class, 'id_categoria', 'id_categoria');
    }
}
