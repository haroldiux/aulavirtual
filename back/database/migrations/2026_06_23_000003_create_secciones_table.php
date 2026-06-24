<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('curso_id');
            $table->unsignedBigInteger('sisa_unidad_id')->nullable();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(1);
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->foreign('curso_id')->references('id')->on('cursos')->cascadeOnDelete();
            $table->index('orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secciones');
    }
};