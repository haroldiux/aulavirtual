<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversacion extends Model
{
    use HasFactory;

    protected $table = 'conversaciones';

    protected $fillable = [
        'curso_id',
        'asunto',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function participantes(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'conversacion_participantes', 'conversacion_id', 'usuario_id')
            ->withTimestamps();
    }

    public function mensajes(): HasMany
    {
        return $this->hasMany(Mensaje::class, 'conversacion_id');
    }
}
