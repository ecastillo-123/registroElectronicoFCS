<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AuditObserver
{
    /** Attributes that never justify an audit event on their own. */
    private const IGNORED_CHANGES = ['updated_at', 'remember_token'];

    public function created(Model $model): void
    {
        AuditService::appendGeneric('created', $model, null, $model->toArray());
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), self::IGNORED_CHANGES);

        if ($changes === []) {
            return;
        }

        $previous = Arr::except(
            Arr::only($model->getOriginal(), array_keys($changes)),
            $model->getHidden(),
        );

        AuditService::appendGeneric('updated', $model, $previous, $model->toArray());
    }

    public function deleted(Model $model): void
    {
        AuditService::appendGeneric('deleted', $model, $model->toArray(), null);
    }
}
