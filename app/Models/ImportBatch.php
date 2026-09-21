<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    use HasFactory;
    protected $fillable = ['nombre_archivo', 'sha256', 'total_filas', 'filas_aceptadas', 'filas_rechazadas', 'estado', 'errores', 'subido_por'];
    protected $casts = ['errores' => 'array'];
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'subido_por'); }
}
