<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Iva extends Model
{
    protected $table = 'iva';
    protected $primaryKey = 'id_iva';
    public $timestamps = false;

    protected $fillable = [
        'monto',
        'CODIGO_SRI',
        'Codigo_Porc_Iva',
        'estado'
    ];
}
