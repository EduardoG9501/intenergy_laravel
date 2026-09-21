<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Obra extends Model
{
    protected $table = 'obra';
    public $timestamps = false;

    protected $fillable = [
        'id_proyecto',
        'nombre',
        'estado',
        'fecha_registro'
    ];

    public function proyecto()
    {
        return $this->belongsTo(Proyecto::class, 'id_proyecto');
    }
}
