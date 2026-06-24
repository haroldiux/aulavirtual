<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoCalendario extends Model
{
    use HasFactory;

    protected $table = 'eventos_calendario';

    protected $fillable = [
        'curso_id',
        'actividad_id',
        'titulo',
        'descripcion',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'todo_el_dia',
        'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
            'todo_el_dia' => 'boolean',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }
}
