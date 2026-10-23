<?php

use App\Enums\DiaSemanaEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF.02: Franjas horarias no disponibles del jugador (clases, estudio, certámenes).
 *
 * - Bloqueo recurrente: es_recurrente = true, se repite cada semana en dia_semana_iso.
 * - Bloqueo puntual:    es_recurrente = false, aplica solo en fecha_especifica.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bloqueos_horarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Se conserva el nombre en español: es el contrato que consume Ionic.
            $table->enum('dia_semana', DiaSemanaEnum::values());
            $table->unsignedTinyInteger('dia_semana_iso');
            $table->date('fecha_especifica')->nullable();
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('motivo', 100)->nullable();
            $table->string('ubicacion', 150)->nullable();
            $table->boolean('es_recurrente')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'dia_semana_iso', 'hora_inicio', 'hora_fin'], 'idx_bloqueos_lookup');
            $table->index(['user_id', 'fecha_especifica'], 'idx_bloqueos_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueos_horarios');
    }
};
