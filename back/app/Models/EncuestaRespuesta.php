<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EncuestaRespuesta extends Model
{
    use HasFactory;

    protected $table = 'encuesta_respuestas';

    protected $fillable = [
        'actividad_id',
        'estudiante_id',
        'respuestas',
    ];

    protected function casts(): array
    {
        return [
            'respuestas' => 'array',
        ];
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'estudiante_id');
    }
}
