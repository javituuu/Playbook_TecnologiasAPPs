<?php

use App\Domain\Enums\EstadoInvitacionEnum;
use App\Domain\Enums\EstadoLigaEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ligas/torneos y la invitación/inscripción de equipos (RF.07 / RF.08).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disciplina_id')->constrained('disciplinas')->restrictOnDelete();
            $table->foreignId('organizador_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('nombre', 150);
            $table->string('temporada', 20)->nullable();
            $table->enum('estado', EstadoLigaEnum::values())
                ->default(EstadoLigaEnum::BORRADOR->value);
            $table->unsignedSmallInteger('descanso_minimo_horas')->default(24);
            $table->unsignedSmallInteger('tolerancia_penalizacion_max')->default(25);
            $table->timestamps();
        });

        Schema::create('inscripciones_liga', function (Blueprint $table) {
            $table->id();
            $table->foreignId('liga_id')->constrained('ligas')->cascadeOnDelete();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->string('codigo_invitacion', 32)->unique();
            $table->enum('estado_invitacion', EstadoInvitacionEnum::values())
                ->default(EstadoInvitacionEnum::PENDIENTE->value);
            $table->boolean('roster_validado')->default(false);
            $table->timestamp('respondido_en')->nullable();
            $table->timestamps();

            $table->unique(['liga_id', 'equipo_id'], 'uk_inscripcion_liga_equipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones_liga');
        Schema::dropIfExists('ligas');
    }
};
