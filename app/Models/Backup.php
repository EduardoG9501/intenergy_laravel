<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    protected $table = 'backup';
    public $timestamps = false;

    protected $fillable = [
        'fecha_crea',
        'ruta',
    ];
}
