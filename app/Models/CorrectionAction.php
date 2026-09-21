<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectionAction extends Model
{
    use HasFactory;
    protected $fillable = ['correction_request_id', 'accion', 'actor_user_id', 'estado_anterior', 'estado_nuevo', 'nota'];
    public function correctionRequest(): BelongsTo { return $this->belongsTo(CorrectionRequest::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_user_id'); }
}
