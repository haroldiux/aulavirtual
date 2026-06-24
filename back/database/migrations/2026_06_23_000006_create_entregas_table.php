<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actividad_id');
            $table->unsignedBigInteger('estudiante_id');
            $table->json('contenido')->nullable();
            $table->timestamp('fecha_entrega')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->unsignedBigInteger('intento_cuestionario_id')->nullable();
            $table->timestamps();

            $table->foreign('actividad_id')->references('id')->on('actividades')->cascadeOnDelete();
            $table->foreign('estudiante_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->index(['actividad_id', 'estado']);
            $table->index('estudiante_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entregas');
    }
};