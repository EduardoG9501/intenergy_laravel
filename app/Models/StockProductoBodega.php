<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockProductoBodega extends Model
{
    protected $table = 'stock_productos_bodega';
    protected $primaryKey = 'id_stock_bodega';
    public $timestamps = false;

    protected $fillable = [
        'id_producto',
        'id_bodega_principal',
        'id_bodega_secundaria',
        'cantidad',
        'fecha_actualizacion',
        'estado',
    ];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'id_producto', 'id_producto');
    }

    public function bodegaPrincipal()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega_principal', 'id_bodega');
    }

    public function bodegaSecundaria()
    {
        return $this->belongsTo(Bodega::class, 'id_bodega_secundaria', 'id_bodega');
    }
}
