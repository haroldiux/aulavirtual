<?php

namespace App\Models;

use App\Enums\EstadoMatricula;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Matricula extends Model
{
    use HasFactory;

    protected $table = 'matriculas';

    protected $fillable = [
        'curso_id',
        'estudiante_id',
        'estado',
        'fecha_matricula',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoMatricula::class,
            'fecha_matricula' => 'date',
        ];
    }

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'estudiante_id');
    }
}