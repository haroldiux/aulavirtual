<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entrega_id')->nullable();
            $table->unsignedBigInteger('actividad_id');
            $table->unsignedBigInteger('estudiante_id');
            $table->unsignedBigInteger('curso_id')->nullable();
            $table->decimal('nota', 5, 2);
            $table->decimal('nota_maxima', 5, 2)->default(100);
            $table->decimal('porcentaje', 5, 2)->nullable();
            $table->text('retroalimentacion')->nullable();
            $table->json('rubrica')->nullable();
            $table->unsignedBigInteger('calificado_por')->nullable();
            $table->boolean('sincronizado_externo')->default(false);
            $table->timestamp('fecha_sincronizacion')->nullable();
            $table->timestamps();

            $table->foreign('entrega_id')->references('id')->on('entregas')->nullOnDelete();
            $table->foreign('actividad_id')->references('id')->on('actividades')->cascadeOnDelete();
            $table->foreign('estudiante_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->foreign('curso_id')->references('id')->on('cursos')->nullOnDelete();
            $table->foreign('calificado_por')->references('id')->on('usuarios')->nullOnDelete();
            $table->index('curso_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};