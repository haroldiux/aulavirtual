<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->string('tipo_actividad', 20)->default('teorica');
            $table->string('grupo_calificacion', 30)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropColumn(['tipo_actividad', 'grupo_calificacion']);
        });
    }
};
