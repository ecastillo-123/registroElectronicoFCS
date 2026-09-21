<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_empleado',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'email',
        'telefono',
        'work_center_id',
        'activo',
        'identificador_unico',
        'fecha_activacion',
        'puesto',
        'area',
        'horario_asignado',
        'device_id',
        'aviso_privacidad_aceptado',
        'aviso_privacidad_aceptado_at',
        'hora_entrada',
        'hora_salida',
        'descanso_inicio',
        'descanso_fin',
        'shift_id',
        'fecha_ingreso',
        'fecha_baja',
        'estado_laboral',
        'departamento',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_activacion' => 'date',
        'aviso_privacidad_aceptado' => 'boolean',
        'aviso_privacidad_aceptado_at' => 'datetime',
        'fecha_ingreso' => 'date',
        'fecha_baja' => 'date',
    ];

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(CorrectionRequest::class);
    }

    public function workdayCalculations(): HasMany
    {
        return $this->hasMany(WorkdayCalculation::class);
    }

    public function evidences(): MorphMany
    {
        return $this->morphMany(Evidence::class, 'evidenciable');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombre} {$this->apellido_paterno} {$this->apellido_materno}");
    }
}
