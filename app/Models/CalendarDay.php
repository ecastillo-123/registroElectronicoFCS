<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarDay extends Model
{
    use HasFactory;

    protected $fillable = ['fecha', 'tipo', 'etiqueta'];
    protected $casts = ['fecha' => 'date'];
}
