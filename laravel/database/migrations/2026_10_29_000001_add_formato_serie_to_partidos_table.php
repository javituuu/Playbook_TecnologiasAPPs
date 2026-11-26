<?php

use App\Domain\Enums\FormatoSerieEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega formato de serie (Bo1, Bo3, Bo5) y minutos por partida a la tabla partidos
 * para permitir el cálculo dinámico de duración (n * minutos_por_partida) en el Motor de Conflictos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->enum('formato_serie', FormatoSerieEnum::values())
                ->default(FormatoSerieEnum::BO1->value)
                ->after('tipo');
            $table->unsignedSmallInteger('minutos_por_partida')
                ->default(FormatoSerieEnum::MINUTOS_ESTANDAR_POR_PARTIDA)
                ->after('formato_serie');
        });
    }

    public function down(): void
    {
        Schema::table('partidos', function (Blueprint $table) {
            $table->dropColumn(['formato_serie', 'minutos_por_partida']);
        });
    }
};
