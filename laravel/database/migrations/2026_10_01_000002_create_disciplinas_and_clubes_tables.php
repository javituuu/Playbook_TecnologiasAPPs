<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de disciplinas (LoL, Valorant...) y clubes universitarios.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disciplinas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->string('slug', 50)->unique();
            $table->unsignedTinyInteger('titulares_requeridos')->default(5);
            $table->unsignedTinyInteger('quorum_minimo_practica')->default(4);
            $table->unsignedSmallInteger('duracion_partido_min')->default(90);
            $table->json('posiciones_validas')->nullable();
            $table->timestamps();
        });

        Schema::create('clubes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('siglas', 20)->unique();
            $table->string('logo_url')->nullable();
            $table->foreignId('admin_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clubes');
        Schema::dropIfExists('disciplinas');
    }
};
