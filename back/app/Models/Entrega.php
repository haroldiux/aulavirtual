<?php

namespace App\Models;

use App\Enums\EstadoEntrega;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Entrega extends Model
{
    use HasFactory;

    protected $table = 'entregas';

    protected $fillable = [
        'actividad_id',
        'estudiante_id',
        'contenido',
        'fecha_entrega',
        'estado',
        'intento_cuestionario_id',
    ];

    protected function casts(): array
    {
        return [
            'contenido' => 'array',
            'fecha_entrega' => 'datetime',
            'estado' => EstadoEntrega::class,
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

    public function calificacion(): HasOne
    {
        return $this->hasOne(Calificacion::class);
    }
}