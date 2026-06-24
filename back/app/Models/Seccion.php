<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seccion extends Model
{
    use HasFactory;

    protected $table = 'secciones';

    protected $fillable = [
        'curso_id',
        'sisa_unidad_id',
        'titulo',
        'descripcion',
        'orden',
        'visible',
    ];

    protected function casts(): array
    {
        return [
            'visible' => 'boolean',
            'orden' => 'integer',
            'sisa_unidad_id' => 'integer',
            'curso_id' => 'integer',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(Actividad::class)->orderBy('orden');
    }
}