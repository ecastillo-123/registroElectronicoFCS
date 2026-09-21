<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Evidence extends Model
{
    use HasFactory;
    protected $fillable = ['employee_id', 'evidenciable_type', 'evidenciable_id', 'nombre_archivo', 'mime_type', 'ruta', 'sha256', 'tamano_bytes', 'estado', 'subido_por'];
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function evidenciable(): MorphTo { return $this->morphTo(); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'subido_por'); }
}
