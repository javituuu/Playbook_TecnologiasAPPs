<?php

use App\Enums\EstadoInvitacionEnum;
use App\Enums\RolMembresiaEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Equipos de un club por disciplina y su roster (RF.06 / RF.07).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained('clubes')->cascadeOnDelete();
            $table->foreignId('disciplina_id')->constrained('disciplinas')->restrictOnDelete();
            $table->foreignId('coach_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->string('nombre', 120);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['club_id', 'nombre']);
        });

        Schema::create('equipo_jugador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('posicion_principal', 30)->nullable();
            $table->string('posicion_secundaria', 30)->nullable();
            $table->string('rango_declarativo', 50)->nullable();
            $table->enum('rol_membresia', RolMembresiaEnum::values())
                ->default(RolMembresiaEnum::EN_PRUEBA->value);
            $table->enum('estado_invitacion', EstadoInvitacionEnum::values())
                ->default(EstadoInvitacionEnum::PENDIENTE->value);
            $table->boolean('es_principal')->default(false);
            $table->timestamps();

            $table->unique(['equipo_id', 'user_id'], 'uk_equipo_jugador');
            $table->index(['user_id', 'estado_invitacion'], 'idx_equipo_jugador_usuario');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipo_jugador');
        Schema::dropIfExists('equipos');
    }
};
