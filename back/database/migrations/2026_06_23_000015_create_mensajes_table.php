<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversacion_id');
            $table->unsignedBigInteger('remitente_id');
            $table->text('contenido');
            $table->json('adjuntos')->nullable();
            $table->boolean('leido')->default(false);
            $table->timestamps();

            $table->foreign('conversacion_id')->references('id')->on('conversaciones')->cascadeOnDelete();
            $table->foreign('remitente_id')->references('id')->on('usuarios')->cascadeOnDelete();

            $table->index(['conversacion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
