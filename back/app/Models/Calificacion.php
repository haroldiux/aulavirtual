<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Calificacion extends Model
{
    use HasFactory;

    protected $table = 'calificaciones';

    protected $fillable = [
        'entrega_id',
        'actividad_id',
        'estudiante_id',
        'curso_id',
        'nota',
        'nota_maxima',
        'porcentaje',
        'retroalimentacion',
        'rubrica',
        'calificado_por',
        'sincronizado_externo',
        'fecha_sincronizacion',
    ];

    protected function casts(): array
    {
        return [
            'nota' => 'decimal:2',
            'nota_maxima' => 'decimal:2',
            'porcentaje' => 'decimal:2',
            'rubrica' => 'array',
            'sincronizado_externo' => 'boolean',
            'fecha_sincronizacion' => 'datetime',
        ];
    }

    public function entrega(): BelongsTo
    {
        return $this->belongsTo(Entrega::class);
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'estudiante_id');
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function calificador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'calificado_por');
    }
}