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
            $table->string('identificador_unico')->nullable();
            $table->date('fecha_activacion')->nullable();
            $table->string('puesto')->nullable();
            $table->string('area')->nullable();
            $table->string('horario_asignado')->nullable();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('device_id');
            $table->dropColumn(['identificador_unico', 'fecha_activacion', 'puesto', 'area', 'horario_asignado']);
        });
    }
};
