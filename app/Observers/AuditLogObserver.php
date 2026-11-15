<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuditLogObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->logEvent($model, 'INSERT', null, $this->filterSensitive($model->getAttributes()));
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $filteredChanges = $this->filterSensitive($changes);
        $filteredOriginal = $this->filterSensitive(array_intersect_key($model->getOriginal(), $changes));

        $this->logEvent($model, 'UPDATE', $filteredOriginal, $filteredChanges);
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->logEvent($model, 'DELETE', $this->filterSensitive($model->getAttributes()), null);
    }

    /**
     * Core logging dispatcher.
     */
    protected function logEvent(Model $model, string $eventType, ?array $oldValues, ?array $newValues): void
    {
        // Prevent logging audit_logs model itself to avoid infinite loops
        if ($model->getTable() === 'audit_logs') {
            return;
        }

        try {
            DB::table('audit_logs')->insert([
                'user_id' => Auth::id() ?? null,
                'event_type' => $eventType,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey() ?? 0,
                'old_values' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                'new_values' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => request()?->ip() ?? '127.0.0.1',
                'user_agent' => request()?->userAgent() ?? 'System',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Observer should never break business transactions if logging fails
        }
    }

    /**
     * Filter out sensitive columns like password, pin_code, tokens.
     */
    protected function filterSensitive(array $attributes): array
    {
        $sensitiveKeys = ['password', 'pin_code', 'remember_token', 'secret', 'token'];
        foreach ($sensitiveKeys as $key) {
            unset($attributes[$key]);
        }

        return $attributes;
    }
}
