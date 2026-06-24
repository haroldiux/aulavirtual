<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seccion_id');
            $table->string('tipo', 20);
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(1);
            $table->boolean('tiene_nota')->default(true);
            $table->decimal('nota_maxima', 5, 2)->default(100);
            $table->decimal('peso', 5, 2)->default(1.00);
            $table->json('config')->nullable();
            $table->unsignedBigInteger('actividadable_id')->nullable();
            $table->string('actividadable_type', 100)->nullable();
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->foreign('seccion_id')->references('id')->on('secciones')->cascadeOnDelete();
            $table->index(['seccion_id', 'orden']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};