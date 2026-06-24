<?php

namespace App\Enums;

enum EstadoMatricula: string
{
    case Activo = 'activo';
    case Inactivo = 'inactivo';
    case Finalizado = 'finalizado';
}