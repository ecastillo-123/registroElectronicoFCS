<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'check_in_id', 'tipo_propuesto', 'fecha_hora_propuesta', 'motivo', 'estado', 'solicitado_por', 'aprobado_por', 'aprobado_at'];
    protected $casts = ['fecha_hora_propuesta' => 'datetime', 'aprobado_at' => 'datetime'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function checkIn(): BelongsTo { return $this->belongsTo(CheckIn::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'solicitado_por'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'aprobado_por'); }
    public function effect(): HasOne { return $this->hasOne(AttendanceCorrectionEffect::class); }
    public function actions(): HasMany { return $this->hasMany(CorrectionAction::class); }
    public function evidences(): MorphMany { return $this->morphMany(Evidence::class, 'evidenciable'); }
}
