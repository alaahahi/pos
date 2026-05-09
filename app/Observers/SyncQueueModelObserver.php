<?php

namespace App\Observers;

use App\Services\SyncQueueService;
use Illuminate\Database\Eloquent\Model;

/**
 * Queues create/update/delete for configurable models when default DB is local SQLite.
 * Does not replace dedicated observers for User, Order, Spatie Role/Permission.
 */
class SyncQueueModelObserver
{
    public function __construct(
        protected SyncQueueService $syncQueueService
    ) {}

    public function created(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }
        $this->syncQueueService->queueInsert($model->getTable(), $model->getKey(), $model->toArray());
    }

    public function updated(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }
        $originalData = $model->getOriginal();
        $this->syncQueueService->queueUpdate($model->getTable(), $model->getKey(), $originalData, $model->toArray());
    }

    public function deleted(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }
        $this->syncQueueService->queueDelete($model->getTable(), $model->getKey());
    }

    /**
     * After soft-delete restore, push full row as insert/update pathway.
     */
    public function restored(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }
        $this->syncQueueService->queueInsert($model->getTable(), $model->getKey(), $model->toArray());
    }

    protected function shouldSkip(Model $model): bool
    {
        if ($this->skipConsole()) {
            return true;
        }

        $table = $model->getTable();
        $noPush = config('sync.no_push_tables', []);

        if (in_array($table, $noPush, true)) {
            return true;
        }

        return false;
    }

    protected function skipConsole(): bool
    {
        if (! app()->runningInConsole()) {
            return false;
        }

        return ! filter_var(config('sync.queue_in_console', false), FILTER_VALIDATE_BOOLEAN);
    }
}
