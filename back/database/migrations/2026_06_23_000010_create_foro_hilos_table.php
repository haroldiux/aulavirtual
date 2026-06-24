<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foro_hilos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actividad_id');
            $table->unsignedBigInteger('autor_id');
            $table->string('titulo');
            $table->text('contenido');
            $table->boolean('fijado')->default(false);
            $table->boolean('anonimo')->default(false);
            $table->timestamps();

            $table->foreign('actividad_id')->references('id')->on('actividades')->cascadeOnDelete();
            $table->foreign('autor_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->index('actividad_id');
        });

        Schema::create('foro_respuestas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hilo_id');
            $table->unsignedBigInteger('autor_id');
            $table->text('contenido');
            $table->boolean('anonimo')->default(false);
            $table->timestamps();

            $table->foreign('hilo_id')->references('id')->on('foro_hilos')->cascadeOnDelete();
            $table->foreign('autor_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->index('hilo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foro_respuestas');
        Schema::dropIfExists('foro_hilos');
    }
};
