<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->time('hora_entrada')->nullable()->after('horario_asignado');
            $table->time('hora_salida')->nullable()->after('hora_entrada');
            $table->time('descanso_inicio')->nullable()->after('hora_salida');
            $table->time('descanso_fin')->nullable()->after('descanso_inicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'hora_entrada',
                'hora_salida',
                'descanso_inicio',
                'descanso_fin',
            ]);
        });
    }
};
