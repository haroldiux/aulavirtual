<?php

namespace App\Enums;

enum EstadoEntrega: string
{
    case Pendiente = 'pendiente';
    case Entregado = 'entregado';
    case Revisado = 'revisado';
    case Rechazado = 'rechazado';
}