<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Incident extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'tipo', 'estado', 'motivo', 'solicitado_por', 'aprobado_por', 'aprobado_at'];
    protected $casts = ['aprobado_at' => 'datetime'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'solicitado_por'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'aprobado_por'); }
    public function evidences(): MorphMany { return $this->morphMany(Evidence::class, 'evidenciable'); }
}
