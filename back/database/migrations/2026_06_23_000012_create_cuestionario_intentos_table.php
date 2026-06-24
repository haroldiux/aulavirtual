<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuestionario_intentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actividad_id');
            $table->unsignedBigInteger('estudiante_id');
            $table->json('respuestas');
            $table->decimal('nota', 5, 2)->default(0);
            $table->integer('intentos_maximos')->default(1);
            $table->timestamp('fecha_inicio')->nullable();
            $table->timestamp('fecha_fin')->nullable();
            $table->timestamps();

            $table->foreign('actividad_id')->references('id')->on('actividades')->cascadeOnDelete();
            $table->foreign('estudiante_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->index(['actividad_id', 'estudiante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuestionario_intentos');
    }
};
