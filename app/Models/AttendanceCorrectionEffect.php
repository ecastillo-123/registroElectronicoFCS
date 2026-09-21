<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceCorrectionEffect extends Model
{
    use HasFactory;
    protected $fillable = ['correction_request_id', 'check_in_id', 'tipo', 'fecha_hora'];
    protected $casts = ['fecha_hora' => 'datetime'];
    public function correctionRequest(): BelongsTo { return $this->belongsTo(CorrectionRequest::class); }
    public function checkIn(): BelongsTo { return $this->belongsTo(CheckIn::class); }
}
