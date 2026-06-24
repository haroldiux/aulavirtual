<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('encuesta_respuestas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actividad_id');
            $table->unsignedBigInteger('estudiante_id');
            $table->json('respuestas');
            $table->timestamps();

            $table->foreign('actividad_id')->references('id')->on('actividades')->cascadeOnDelete();
            $table->foreign('estudiante_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->unique(['actividad_id', 'estudiante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encuesta_respuestas');
    }
};
