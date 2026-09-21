<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Articulo extends Model
{
    protected $table = 'articulos';
    protected $primaryKey = 'id_producto';
    public $timestamps = false;

    protected $fillable = [
        'id_categoria',
        'id_subcategoria',
        'id_imagen',
        'id_usuario',
        'nombre',
        'descripcion',
        'cantidad',
        'precio',
        'fechaCaptura',
        'estado',
        'codigobarra',
        'proveedor',
        'referencia',
        'id_proveedor',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'id_categoria', 'id_categoria');
    }

    public function subcategoria()
    {
        return $this->belongsTo(Categoria::class, 'id_subcategoria', 'id_categoria');
    }

    public function proveedorRel()
    {
        return $this->belongsTo(Proveedor::class, 'id_proveedor', 'id_proveedor');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
