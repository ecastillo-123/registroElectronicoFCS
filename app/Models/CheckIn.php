<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckIn extends Model
{
    use HasFactory;

    public const TIPO_ENTRADA = 'entrada';

    public const TIPO_SALIDA = 'salida';

    public const CHECKIN_TYPE_HUELLA = 'huella';

    public const CHECKIN_TYPE_FACIAL = 'facial';

    public const SYNC_STATUS_NORMAL = 'normal';

    public const SYNC_STATUS_PENDIENTE = 'pendiente';

    protected $fillable = [
        'employee_id',
        'user_id',
        'device_id',
        'work_center_id',
        'tipo',
        'lat',
        'lng',
        'precision_metros',
        'fecha_dispositivo',
        'distancia_metros',
        'dentro_rango',
        'validado',
        'validado_por',
        'validado_at',
        'nota',
        'checkin_type',
        'sync_status',
        'pending_checkin_datetime',
        'synced_at',
        'client_uuid',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'precision_metros' => 'decimal:2',
        'fecha_dispositivo' => 'datetime',
        'distancia_metros' => 'decimal:2',
        'dentro_rango' => 'boolean',
        'validado' => 'boolean',
        'validado_at' => 'datetime',
        'pending_checkin_datetime' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function workCenter(): BelongsTo
    {
        return $this->belongsTo(WorkCenter::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validado_por');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('consulta')) {
            $query->where(function (Builder $query) use ($user): void {
                $query->where('user_id', $user->getKey());

                if ($user->employee_id) {
                    $query->orWhere('employee_id', $user->employee_id);
                }
            });

            return $query;
        }

        if ($user->hasRole('revisor')) {
            $workCenterId = $user->employee?->work_center_id;

            if ($workCenterId) {
                $query->where('work_center_id', $workCenterId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    public function scopeByClientUuid(Builder $query, string $uuid): Builder
    {
        return $query->where('client_uuid', $uuid);
    }

    public function getEstadoLabelAttribute(): string
    {
        return match ($this->validado) {
            true => 'Aprobado',
            false => 'Rechazado',
            default => 'Pendiente',
        };
    }

    public function getDentroRangoLabelAttribute(): string
    {
        return $this->dentro_rango ? 'Dentro' : 'Fuera';
    }

    public function getCheckinTypeLabelAttribute(): string
    {
        return match ($this->checkin_type) {
            self::CHECKIN_TYPE_HUELLA => 'Huella',
            self::CHECKIN_TYPE_FACIAL => 'Facial',
            default => 'N/A',
        };
    }

    public function getSyncStatusLabelAttribute(): string
    {
        return match ($this->sync_status) {
            self::SYNC_STATUS_NORMAL => 'Normal',
            self::SYNC_STATUS_PENDIENTE => 'Pendiente',
            default => 'Normal',
        };
    }
}
