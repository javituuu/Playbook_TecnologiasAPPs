<?php

use App\Domain\Enums\UserRoleEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RF.01: Agrega el rol global y datos de perfil a la tabla users.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', UserRoleEnum::values())
                ->default(UserRoleEnum::JUGADOR->value)
                ->after('password');
            $table->string('avatar_url')->nullable()->after('role');
            $table->string('telefono', 30)->nullable()->after('avatar_url');
            $table->boolean('activo')->default(true)->after('telefono');

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'avatar_url', 'telefono', 'activo']);
        });
    }
};
