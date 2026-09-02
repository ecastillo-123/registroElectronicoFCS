<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'nombre',
        'marca',
        'modelo',
        'plataforma',
        'version_so',
        'app_version',
        'user_id',
        'ultima_conexion_at',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultima_conexion_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }
}
