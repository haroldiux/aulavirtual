<?php

namespace App\Models;

use App\Enums\EstadoCurso;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    use HasFactory;

    protected $table = 'cursos';

    protected $fillable = [
        'sisa_asignatura_id',
        'sisa_grupo_id',
        'codigo',
        'nombre',
        'descripcion',
        'docente_id',
        'carrera_id',
        'sede_id',
        'gestion',
        'estado',
        'imagen_portada',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoCurso::class,
            'config' => 'array',
            'sisa_asignatura_id' => 'integer',
            'sisa_grupo_id' => 'integer',
            'docente_id' => 'integer',
            'carrera_id' => 'integer',
            'sede_id' => 'integer',
        ];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }

    public function secciones(): HasMany
    {
        return $this->hasMany(Seccion::class)->orderBy('orden');
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function estudiantes()
    {
        return $this->belongsToMany(Usuario::class, 'matriculas', 'curso_id', 'estudiante_id')
            ->where('usuarios.rol', 'estudiante')
            ->withPivot('estado', 'fecha_matricula');
    }

    public function calificaciones(): HasMany
    {
        return $this->hasMany(Calificacion::class, 'curso_id');
    }

    public function eventosCalendario(): HasMany
    {
        return $this->hasMany(EventoCalendario::class);
    }

    public function conversaciones(): HasMany
    {
        return $this->hasMany(Conversacion::class);
    }

    public function scopePublicados($query)
    {
        return $query->where('estado', EstadoCurso::Publicado);
    }
}