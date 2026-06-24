<?php

namespace App\Enums;

enum TipoActividad: string
{
    case Leccion = 'leccion';
    case Tarea = 'tarea';
    case Foro = 'foro';
    case Cuestionario = 'cuestionario';
    case Encuesta = 'encuesta';
    case H5p = 'h5p';
}