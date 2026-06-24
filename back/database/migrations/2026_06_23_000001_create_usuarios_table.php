<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sisa_id')->nullable()->unique();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->string('avatar')->nullable();
            $table->string('rol', 20)->default('estudiante');
            $table->unsignedBigInteger('carrera_id')->nullable();
            $table->unsignedBigInteger('sede_id')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_sync')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->index('rol');
            $table->index('carrera_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};