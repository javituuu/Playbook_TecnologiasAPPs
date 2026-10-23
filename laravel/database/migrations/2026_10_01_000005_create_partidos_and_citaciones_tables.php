<?php

use App\Enums\EstadoCitacionEnum;
use App\Enums\EstadoPartidoEnum;
use App\Enums\RolMembresiaEnum;
use App\Enums\TipoPartidoEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partidos (oficiales o prácticas) y citaciones de jugadores (RF.04 / RF.08 / RF.09).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partidos', function (Blueprint $table) {
            $table->id();
            // Nullable: una práctica interna de equipo no pertenece a una liga.
            $table->foreignId('liga_id')->nullable()->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('equipo_local_id')->constrained('equipos')->restrictOnDelete();
            $table->foreignId('equipo_visitante_id')->nullable()
                ->constrained('equipos')->restrictOnDelete();
            $table->enum('tipo', TipoPartidoEnum::values())
                ->default(TipoPartidoEnum::OFICIAL->value);
            $table->unsignedTinyInteger('jornada_numero')->nullable();
            $table->string('bloque_etiqueta', 50)->nullable();
            $table->string('lugar', 150)->nullable();
            $table->dateTime('fecha_hora_inicio');
            $table->dateTime('fecha_hora_fin');
            $table->enum('estado', EstadoPartidoEnum::values())
                ->default(EstadoPartidoEnum::BORRADOR->value);
            $table->timestamps();

            $table->index(['fecha_hora_inicio', 'fecha_hora_fin', 'estado'], 'idx_partidos_rango_temporal');
            $table->index(['liga_id', 'jornada_numero'], 'idx_partidos_liga_jornada');
        });

        Schema::create('citaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partido_id')->constrained('partidos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('equipo_id')->nullable()->constrained('equipos')->nullOnDelete();
            $table->enum('tipo_convocatoria', [
                RolMembresiaEnum::TITULAR->value,
                RolMembresiaEnum::SUPLENTE->value,
            ])->default(RolMembresiaEnum::TITULAR->value);
            $table->string('mensaje_coach')->nullable();
            $table->enum('estado_respuesta', EstadoCitacionEnum::values())
                ->default(EstadoCitacionEnum::PENDIENTE->value);
            $table->timestamp('respondido_en')->nullable();
            $table->timestamps();

            $table->unique(['partido_id', 'user_id'], 'uk_citacion_partido_jugador');
            $table->index(['user_id', 'estado_respuesta'], 'idx_citaciones_jugador_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citaciones');
        Schema::dropIfExists('partidos');
    }
};
