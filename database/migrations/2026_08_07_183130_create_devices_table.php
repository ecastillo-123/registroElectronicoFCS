<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('nombre')->nullable();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('plataforma')->nullable();
            $table->string('version_so')->nullable();
            $table->string('app_version')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('ultima_conexion_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
