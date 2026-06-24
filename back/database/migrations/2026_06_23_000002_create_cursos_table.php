<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sisa_asignatura_id')->nullable();
            $table->unsignedBigInteger('sisa_grupo_id')->nullable();
            $table->string('codigo', 50)->nullable();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('docente_id');
            $table->unsignedBigInteger('carrera_id')->nullable();
            $table->unsignedBigInteger('sede_id')->nullable();
            $table->string('gestion', 20)->nullable();
            $table->string('estado', 20)->default('borrador');
            $table->string('imagen_portada')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();

            $table->foreign('docente_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->index('estado');
            $table->index('gestion');
            $table->index('carrera_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};