<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('docente_id');
            $table->enum('categoria', ['actividad', 'rubrica', 'preguntas', 'curso']);
            $table->string('tipo'); // ej. 'tarea', 'cuestionario', 'foro'
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->json('datos');
            $table->integer('uso_count')->default(0);
            $table->boolean('publica')->default(false);
            $table->timestamps();

            $table->foreign('docente_id')->references('id')->on('usuarios')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas');
    }
};
