<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_center_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('tipo', ['entrada', 'salida']);
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->decimal('precision_metros', 8, 2)->nullable();
            $table->timestamp('fecha_dispositivo')->nullable();
            $table->decimal('distancia_metros', 8, 2)->nullable();
            $table->boolean('dentro_rango')->default(false);
            $table->boolean('validado')->nullable()->comment('null=pendiente, true=aprobado, false=rechazado');
            $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validado_at')->nullable();
            $table->text('nota')->nullable();
            $table->timestamps();

            $table->index(['work_center_id', 'tipo']);
            $table->index(['employee_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
