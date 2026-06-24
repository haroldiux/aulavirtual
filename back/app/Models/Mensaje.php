<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mensaje extends Model
{
    use HasFactory;

    protected $table = 'mensajes';

    protected $fillable = [
        'conversacion_id',
        'remitente_id',
        'contenido',
        'adjuntos',
        'leido',
    ];

    protected function casts(): array
    {
        return [
            'adjuntos' => 'array',
            'leido' => 'boolean',
        ];
    }

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class);
    }

    public function remitente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'remitente_id');
    }
}
