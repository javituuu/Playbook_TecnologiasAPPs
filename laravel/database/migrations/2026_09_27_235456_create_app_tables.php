<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidos', function (Blueprint $table) {
            $table->id();
            $table->string('liga');
            $table->string('equipo_local');
            $table->string('equipo_visitante');
            $table->string('lugar');
            $table->dateTime('fecha_hora_inicio');
            $table->dateTime('fecha_hora_fin')->nullable();
            $table->string('estado')->default('programado');
            $table->timestamps();
        });

        Schema::create('citaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('partido_id')->constrained('partidos')->onDelete('cascade');
            $table->string('mensaje_coach')->nullable();
            $table->enum('estado_respuesta', ['pendiente', 'confirmado', 'rechazado'])->default('pendiente');
            $table->timestamps();
        });

        Schema::create('bloqueos_horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('dia_semana');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos_horarios');
        Schema::dropIfExists('citaciones');
        Schema::dropIfExists('partidos');
    }
};
