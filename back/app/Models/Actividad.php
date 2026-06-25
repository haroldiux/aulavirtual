<?php

namespace App\Models;

use App\Enums\TipoActividad;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Actividad extends Model
{
    use HasFactory;

    protected $table = 'actividades';

    protected $fillable = [
        'seccion_id',
        'tipo',
        'titulo',
        'descripcion',
        'orden',
        'tiene_nota',
        'nota_maxima',
        'peso',
        'config',
        'actividadable_id',
        'actividadable_type',
        'visible',
        'tipo_actividad',
        'grupo_calificacion',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoActividad::class,
            'tiene_nota' => 'boolean',
            'visible' => 'boolean',
            'orden' => 'integer',
            'nota_maxima' => 'decimal:2',
            'peso' => 'decimal:2',
            'config' => 'array',
        ];
    }

    public function seccion(): BelongsTo
    {
        return $this->belongsTo(Seccion::class);
    }

    public function entregas(): HasMany
    {
        return $this->hasMany(Entrega::class);
    }
}