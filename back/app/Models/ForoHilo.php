<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForoHilo extends Model
{
    use HasFactory;

    protected $table = 'foro_hilos';

    protected $fillable = [
        'actividad_id',
        'autor_id',
        'titulo',
        'contenido',
        'fijado',
        'anonimo',
    ];

    protected function casts(): array
    {
        return [
            'fijado' => 'boolean',
            'anonimo' => 'boolean',
        ];
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'autor_id');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(ForoRespuesta::class, 'hilo_id')->oldest();
    }
}
