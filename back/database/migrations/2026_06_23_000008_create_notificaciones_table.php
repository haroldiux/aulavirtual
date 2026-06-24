<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->string('tipo', 50);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('icono', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->json('data')->nullable();
            $table->boolean('leida')->default(false);
            $table->string('ruta')->nullable();
            $table->timestamps();

            $table->foreign('usuario_id')->references('id')->on('usuarios')->cascadeOnDelete();
            $table->index(['usuario_id', 'leida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};