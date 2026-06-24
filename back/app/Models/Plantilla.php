<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Plantilla extends Model
{
    use HasFactory;

    protected $table = 'plantillas';

    protected $fillable = [
        'docente_id',
        'categoria',
        'tipo',
        'nombre',
        'descripcion',
        'datos',
        'uso_count',
        'publica',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'publica' => 'boolean',
            'uso_count' => 'integer',
        ];
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'docente_id');
    }
}
