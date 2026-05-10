<?php

namespace App\Observers;

use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

class AuditableModelObserver
{
    protected static array $pendingUpdates = [];

    protected static array $pendingDeletes = [];

    public function __construct(
        protected AuditLogService $auditLogs
    ) {
    }

    public function updating(Model $model): void
    {
        if (! $this->auditLogs->shouldLogModel($model)) {
            return;
        }

        $changes = $model->getDirty();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $before = [];

        foreach (array_keys($changes) as $attribute) {
            $before[$attribute] = $model->getOriginal($attribute);
        }

        self::$pendingUpdates[spl_object_id($model)] = [
            'before' => $before,
            'after' => $changes,
        ];
    }

    public function updated(Model $model): void
    {
        if (! $this->auditLogs->shouldLogModel($model)) {
            return;
        }

        $key = spl_object_id($model);
        $payload = self::$pendingUpdates[$key] ?? null;
        unset(self::$pendingUpdates[$key]);

        if (! $payload) {
            return;
        }

        $this->auditLogs->logModelEvent($model, 'updated', $payload['before'], $payload['after']);
    }

    public function created(Model $model): void
    {
        if (! $this->auditLogs->shouldLogModel($model)) {
            return;
        }

        $this->auditLogs->logModelEvent($model, 'created', [], $model->getAttributes());
    }

    public function deleting(Model $model): void
    {
        if (! $this->auditLogs->shouldLogModel($model)) {
            return;
        }

        self::$pendingDeletes[spl_object_id($model)] = $model->getAttributes();
    }

    public function deleted(Model $model): void
    {
        if (! $this->auditLogs->shouldLogModel($model)) {
            return;
        }

        $key = spl_object_id($model);
        $before = self::$pendingDeletes[$key] ?? $model->getAttributes();
        unset(self::$pendingDeletes[$key]);

        $this->auditLogs->logModelEvent($model, 'deleted', $before, []);
    }
}
