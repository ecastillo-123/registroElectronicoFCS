<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditEvent extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = ['tipo_evento', 'tipo_entidad', 'entidad_id', 'actor_id', 'ocurrido_at', 'ip', 'user_agent', 'anterior', 'actual', 'motivo', 'hash', 'hash_anterior'];
    protected $casts = ['ocurrido_at' => 'datetime', 'anterior' => 'array', 'actual' => 'array'];
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
