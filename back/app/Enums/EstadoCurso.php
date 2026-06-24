<?php

namespace App\Enums;

enum EstadoCurso: string
{
    case Borrador = 'borrador';
    case Publicado = 'publicado';
    case Archivado = 'archivado';
}