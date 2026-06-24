<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('curso_id');
            $table->unsignedBigInteger('estudiante_id');
            $table->string('estado', 20)->default('activo');
            $table->date('fecha_matricula')->nullable();
            $table->timestamps();

            $table->foreign('curso_id')->references('id')->on('cursos')->cascadeOnDelete();
            $table->foreign('estudiante_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->unique(['curso_id', 'estudiante_id']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};