<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkdayCalculation extends Model
{
    use HasFactory;
    protected $fillable = ['employee_id', 'fecha', 'rule_set_id', 'version_regla', 'minutos_programados', 'minutos_brutos', 'minutos_descanso', 'minutos_trabajados', 'minutos_ordinarios', 'minutos_extra', 'minutos_retardo', 'estado', 'detalles', 'calculado_at'];
    protected $casts = ['fecha' => 'date', 'detalles' => 'array', 'calculado_at' => 'datetime'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function ruleSet(): BelongsTo { return $this->belongsTo(RuleSet::class); }
}
