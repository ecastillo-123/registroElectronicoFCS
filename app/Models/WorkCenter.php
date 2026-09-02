<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkCenter extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'nombre',
        'direccion',
        'lat',
        'lng',
        'radio_metros',
        'activo',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'radio_metros' => 'integer',
        'activo' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    /**
     * Distancia en metros entre el centro de trabajo y unas coordenadas (fórmula de Haversine).
     */
    public function distanceTo(float $lat, float $lng): float
    {
        $earthRadius = 6371000.0;

        $latFrom = deg2rad((float) $this->lat);
        $lngFrom = deg2rad((float) $this->lng);
        $latTo = deg2rad($lat);
        $lngTo = deg2rad($lng);

        $dLat = $latTo - $latFrom;
        $dLng = $lngTo - $lngFrom;

        $a = sin($dLat / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($dLng / 2) ** 2;

        return round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}
