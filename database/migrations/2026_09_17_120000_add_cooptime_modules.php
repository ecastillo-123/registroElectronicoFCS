<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unsignedInteger('minutos_descanso')->default(0);
            $table->unsignedInteger('tolerancia_minutos')->default(10);
            $table->decimal('horas_semanales', 6, 2)->default(40);
            $table->decimal('horas_diarias', 6, 2)->default(8);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('employees', function (Blueprint $table): void {
            $table->foreignId('shift_id')->nullable()->after('device_id')->constrained('shifts')->nullOnDelete();
            $table->date('fecha_ingreso')->nullable()->after('fecha_activacion');
            $table->date('fecha_baja')->nullable()->after('fecha_ingreso');
            $table->string('estado_laboral')->default('activo')->after('activo');
            $table->string('departamento')->nullable()->after('area');
        });

        Schema::create('calendar_days', function (Blueprint $table): void {
            $table->id();
            $table->date('fecha');
            $table->enum('tipo', ['laboral', 'descanso', 'festivo', 'vacaciones']);
            $table->string('etiqueta')->nullable();
            $table->timestamps();
            $table->unique('fecha');
        });

        Schema::create('rule_sets', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('version');
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->decimal('horas_semanales', 6, 2)->default(40);
            $table->decimal('horas_diarias', 6, 2)->default(8);
            $table->unsignedInteger('umbral_extra_minutos')->default(0);
            $table->decimal('multiplicador_extra', 6, 2)->default(2);
            $table->unsignedInteger('tolerancia_retardo_minutos')->default(10);
            $table->decimal('maximo_horas_diarias', 6, 2)->default(12);
            $table->json('parametros')->nullable();
            $table->timestamps();
            $table->index(['vigente_desde', 'vigente_hasta', 'activo']);
        });

        Schema::create('incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['retardo', 'inasistencia', 'permiso', 'falla_dispositivo', 'registro_faltante', 'vacaciones', 'descanso', 'otro']);
            $table->enum('estado', ['borrador', 'pendiente', 'aprobado', 'rechazado', 'cancelado'])->default('pendiente');
            $table->text('motivo');
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_at')->nullable();
            $table->timestamps();
            $table->index(['estado', 'tipo']);
        });

        Schema::create('correction_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('check_in_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo_propuesto', ['entrada', 'salida']);
            $table->timestamp('fecha_hora_propuesta');
            $table->text('motivo');
            $table->enum('estado', ['borrador', 'pendiente', 'aprobado', 'rechazado', 'cancelado'])->default('pendiente');
            $table->foreignId('solicitado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_at')->nullable();
            $table->timestamps();
            $table->index(['estado', 'employee_id']);
        });

        Schema::create('attendance_correction_effects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('correction_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('check_in_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['entrada', 'salida']);
            $table->timestamp('fecha_hora');
            $table->timestamps();
        });

        Schema::create('correction_actions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('correction_request_id')->constrained()->cascadeOnDelete();
            $table->string('accion');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo')->nullable();
            $table->text('nota')->nullable();
            $table->timestamps();
        });

        Schema::create('evidences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('evidenciable');
            $table->string('nombre_archivo');
            $table->string('mime_type');
            $table->string('ruta');
            $table->char('sha256', 64);
            $table->unsignedBigInteger('tamano_bytes');
            $table->enum('estado', ['activo', 'revocado'])->default('activo');
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('signature_receipts', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_solicitud');
            $table->unsignedBigInteger('solicitud_id');
            $table->foreignId('firmante_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nombre_firmante');
            $table->timestamp('firmado_at');
            $table->string('metodo');
            $table->char('hash_documento', 64);
            $table->ipAddress('ip')->nullable();
            $table->index(['tipo_solicitud', 'solicitud_id']);
        });

        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->string('tipo_evento');
            $table->string('tipo_entidad');
            $table->string('entidad_id');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ocurrido_at');
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('anterior')->nullable();
            $table->json('actual')->nullable();
            $table->text('motivo')->nullable();
            $table->char('hash', 64)->unique();
            $table->char('hash_anterior', 64)->nullable();
            $table->index(['tipo_entidad', 'entidad_id', 'ocurrido_at']);
        });

        Schema::create('workday_calculations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->foreignId('rule_set_id')->nullable()->constrained()->nullOnDelete();
            $table->string('version_regla')->nullable();
            $table->unsignedInteger('minutos_programados')->default(0);
            $table->unsignedInteger('minutos_brutos')->default(0);
            $table->unsignedInteger('minutos_descanso')->default(0);
            $table->unsignedInteger('minutos_trabajados')->default(0);
            $table->unsignedInteger('minutos_ordinarios')->default(0);
            $table->unsignedInteger('minutos_extra')->default(0);
            $table->unsignedInteger('minutos_retardo')->default(0);
            $table->string('estado')->default('calculado');
            $table->json('detalles')->nullable();
            $table->timestamp('calculado_at')->useCurrent();
            $table->timestamps();
            $table->unique(['employee_id', 'fecha']);
        });

        Schema::create('alerts', function (Blueprint $table): void {
            $table->id();
            $table->string('severidad');
            $table->string('tipo');
            $table->string('titulo');
            $table->text('mensaje');
            $table->string('entidad_tipo')->nullable();
            $table->string('entidad_id')->nullable();
            $table->timestamp('resuelta_at')->nullable();
            $table->timestamps();
            $table->index(['resuelta_at', 'severidad']);
        });

        Schema::create('import_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre_archivo');
            $table->char('sha256', 64);
            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('filas_aceptadas')->default(0);
            $table->unsignedInteger('filas_rechazadas')->default(0);
            $table->string('estado');
            $table->json('errores')->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('workday_calculations');
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('signature_receipts');
        Schema::dropIfExists('evidences');
        Schema::dropIfExists('correction_actions');
        Schema::dropIfExists('attendance_correction_effects');
        Schema::dropIfExists('correction_requests');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('rule_sets');
        Schema::dropIfExists('calendar_days');
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('shift_id');
            $table->dropColumn(['fecha_ingreso', 'fecha_baja', 'estado_laboral', 'departamento']);
        });
        Schema::dropIfExists('shifts');
    }
};
