<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('curso_id')->nullable();
            $table->string('asunto')->nullable();
            $table->timestamps();

            $table->foreign('curso_id')->references('id')->on('cursos')->cascadeOnDelete();
        });

        Schema::create('conversacion_participantes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversacion_id');
            $table->unsignedBigInteger('usuario_id');
            $table->timestamps();

            $table->foreign('conversacion_id')->references('id')->on('conversaciones')->cascadeOnDelete();
            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();

            $table->unique(['conversacion_id', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversacion_participantes');
        Schema::dropIfExists('conversaciones');
    }
};
