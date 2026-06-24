<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracion extends Model
{
    protected $table = 'configuraciones';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'valor',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'array',
        ];
    }
}