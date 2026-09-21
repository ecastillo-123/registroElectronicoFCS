<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = ['codigo', 'nombre', 'hora_inicio', 'hora_fin', 'minutos_descanso', 'tolerancia_minutos', 'horas_semanales', 'horas_diarias', 'activo'];

    protected $casts = ['activo' => 'boolean', 'horas_semanales' => 'decimal:2', 'horas_diarias' => 'decimal:2'];

    public function employees(): HasMany { return $this->hasMany(Employee::class); }
}
