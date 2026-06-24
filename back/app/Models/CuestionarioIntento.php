<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuestionarioIntento extends Model
{
    use HasFactory;

    protected $table = 'cuestionario_intentos';

    protected $fillable = [
        'actividad_id',
        'estudiante_id',
        'respuestas',
        'nota',
        'intentos_maximos',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return [
            'respuestas' => 'array',
            'nota' => 'decimal:2',
            'intentos_maximos' => 'integer',
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
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
