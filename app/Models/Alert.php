<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;
    protected $fillable = ['severidad', 'tipo', 'titulo', 'mensaje', 'entidad_tipo', 'entidad_id', 'resuelta_at'];
    protected $casts = ['resuelta_at' => 'datetime'];
}
