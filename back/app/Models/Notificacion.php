<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_id',
        'tipo',
        'titulo',
        'descripcion',
        'icono',
        'color',
        'data',
        'leida',
        'ruta',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'leida' => 'boolean',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}