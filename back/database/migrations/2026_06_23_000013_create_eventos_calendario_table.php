<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_calendario', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('curso_id')->nullable();
            $table->unsignedBigInteger('actividad_id')->nullable();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('tipo', 30); // entrega, evaluacion, clase, evento_institucional
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin')->nullable();
            $table->boolean('todo_el_dia')->default(false);
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamps();

            $table->foreign('curso_id')->references('id')->on('cursos')->cascadeOnDelete();
            $table->foreign('actividad_id')->references('id')->on('actividades')->cascadeOnDelete();
            $table->foreign('creado_por')->references('id')->on('usuarios')->nullOnDelete();

            $table->index(['curso_id', 'fecha_inicio']);
            $table->index('fecha_inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_calendario');
    }
};
