<?php

namespace App\Enums;

enum Rol: string
{
    case Estudiante = 'estudiante';
    case Docente = 'docente';
    case Director = 'director';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Estudiante => 'Estudiante',
            self::Docente => 'Docente',
            self::Director => 'Director',
            self::Admin => 'Administrador',
        };
    }
}