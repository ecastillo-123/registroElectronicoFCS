<?php

namespace App\Services;

use App\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Counter of active withoutGenericEvents() scopes. While > 0 the
     * AuditObserver stays silent so semantic flows (approve/reject, API
     * check-in enrichment) can log their own richer event instead.
     */
    private static int $muted = 0;

    public static function withoutGenericEvents(callable $callback): mixed
    {
        self::$muted++;

        try {
            return $callback();
        } finally {
            self::$muted--;
        }
    }

    /**
     * Entry point used by the AuditObserver for plain CRUD writes. Skips
     * muted scopes and console runs (seeders, artisan commands) so the
     * hash chain only carries real user-driven events.
     */
    public static function appendGeneric(string $eventType, Model $model, ?array $previous, ?array $current): void
    {
        if (self::$muted > 0 || app()->runningInConsole()) {
            return;
        }

        self::append($eventType, class_basename($model), $model->getKey(), [
            'previous' => $previous,
            'current' => $current,
        ]);
    }

    public static function append(string $eventType, string $entityType, string|int $entityId, array $data = []): AuditEvent
    {
        return DB::transaction(function () use ($eventType, $entityType, $entityId, $data): AuditEvent {
            $last = AuditEvent::query()->lockForUpdate()->latest('id')->first();
            $occurredAt = now();
            $previousHash = $last?->hash;
            $payload = [
                'event' => $eventType,
                'entity_type' => $entityType,
                'entity_id' => (string) $entityId,
                'actor_id' => $data['actor_id'] ?? auth()->id(),
                'previous_hash' => $previousHash,
                'occurred_at' => $occurredAt->toISOString(),
                'previous' => $data['previous'] ?? null,
                'current' => $data['current'] ?? null,
                'reason' => $data['reason'] ?? null,
            ];

            return AuditEvent::create([
                'tipo_evento' => $eventType,
                'tipo_entidad' => $entityType,
                'entidad_id' => (string) $entityId,
                'actor_id' => $payload['actor_id'],
                'ocurrido_at' => $occurredAt,
                'ip' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 1000),
                'anterior' => $data['previous'] ?? null,
                'actual' => $data['current'] ?? null,
                'motivo' => $data['reason'] ?? null,
                'hash_anterior' => $previousHash,
                'hash' => hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            ]);
        });
    }

    public static function verify(): array
    {
        $previousHash = null;
        $checked = 0;

        foreach (AuditEvent::query()->orderBy('id')->cursor() as $event) {
            if ($event->hash_anterior !== $previousHash) {
                return ['valid' => false, 'checked' => $checked, 'error' => "Cadena rota en el evento {$event->id}."];
            }
            $previousHash = $event->hash;
            $checked++;
        }

        return ['valid' => true, 'checked' => $checked, 'error' => null];
    }
}
