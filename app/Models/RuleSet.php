<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RuleSet extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'version', 'vigente_desde', 'vigente_hasta', 'activo', 'horas_semanales', 'horas_diarias', 'umbral_extra_minutos', 'multiplicador_extra', 'tolerancia_retardo_minutos', 'maximo_horas_diarias', 'parametros'];
    protected $casts = ['vigente_desde' => 'date', 'vigente_hasta' => 'date', 'activo' => 'boolean', 'parametros' => 'array'];

    public function scopeEffectiveFor(Builder $query, CarbonInterface $date): Builder
    {
        return $query->where('activo', true)->whereDate('vigente_desde', '<=', $date)->where(function (Builder $query) use ($date): void {
            $query->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $date);
        })->orderByDesc('vigente_desde');
    }
}
