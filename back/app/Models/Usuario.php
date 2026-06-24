<?php

namespace App\Models;

use App\Enums\Rol;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'sisa_id',
        'nombre',
        'email',
        'password',
        'avatar',
        'rol',
        'carrera_id',
        'sede_id',
        'activo',
        'ultimo_sync',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'rol' => Rol::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'ultimo_sync' => 'datetime',
        ];
    }

    public function cursosComoDocente()
    {
        return $this->hasMany(Curso::class, 'docente_id');
    }

    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'estudiante_id');
    }

    public function cursosComoEstudiante()
    {
        return $this->belongsToMany(Curso::class, 'matriculas', 'estudiante_id', 'curso_id')
            ->withPivot('estado', 'fecha_matricula')
            ->using(Matricula::class);
    }

    public function notificaciones()
    {
        return $this->hasMany(Notificacion::class, 'usuario_id');
    }

    public function entregas()
    {
        return $this->hasMany(Entrega::class, 'estudiante_id');
    }

    public function calificaciones()
    {
        return $this->hasMany(Calificacion::class, 'estudiante_id');
    }

    public function conversaciones()
    {
        return $this->belongsToMany(Conversacion::class, 'conversacion_participantes', 'usuario_id', 'conversacion_id')
            ->withTimestamps();
    }

    public function plantillas()
    {
        return $this->hasMany(Plantilla::class, 'docente_id');
    }

    public function esDocente(): bool
    {
        return $this->rol === Rol::Docente;
    }

    public function esEstudiante(): bool
    {
        return $this->rol === Rol::Estudiante;
    }

    public function esDirector(): bool
    {
        return $this->rol === Rol::Director;
    }

    public function esAdmin(): bool
    {
        return $this->rol === Rol::Admin;
    }
}