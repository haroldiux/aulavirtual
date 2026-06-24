<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForoRespuesta extends Model
{
    use HasFactory;

    protected $table = 'foro_respuestas';

    protected $fillable = [
        'hilo_id',
        'autor_id',
        'contenido',
        'anonimo',
    ];

    protected function casts(): array
    {
        return [
            'anonimo' => 'boolean',
        ];
    }

    public function hilo(): BelongsTo
    {
        return $this->belongsTo(ForoHilo::class, 'hilo_id');
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'autor_id');
    }
}
