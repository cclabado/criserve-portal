<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AuditLogService
{
    protected array $sensitiveAttributes = [
        'password',
        'remember_token',
        'google_access_token',
        'google_refresh_token',
        'mfa_code_hash',
        'mfa_remember_token_hash',
    ];

    public function log(?Request $request, string $action, ?Model $auditable = null, array $metadata = [], $user = null): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        AuditLog::create([
            'user_id' => $user?->id ?? $request?->user()?->id,
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => array_merge($this->baseMetadata($request), $metadata),
        ]);
    }

    public function shouldLogModel(Model $model): bool
    {
        return ! $model instanceof AuditLog;
    }

    public function logModelEvent(Model $model, string $event, array $before = [], array $after = [], array $metadata = []): void
    {
        $request = request();

        $this->log(
            $request instanceof Request ? $request : null,
            'model.'.Str::snake(class_basename($model)).'.'.$event,
            $model,
            array_merge([
                'model' => class_basename($model),
                'before' => $this->sanitizeAttributes($model, $before),
                'after' => $this->sanitizeAttributes($model, $after),
            ], $metadata)
        );
    }

    protected function baseMetadata(?Request $request): array
    {
        if (! $request) {
            return [];
        }

        return [
            'method' => $request->method(),
            'path' => $request->path(),
            'route_name' => $request->route()?->getName(),
        ];
    }

    protected function sanitizeAttributes(Model $model, array $attributes): array
    {
        $hidden = method_exists($model, 'getHidden') ? $model->getHidden() : [];
        $blocked = array_unique(array_merge($hidden, $this->sensitiveAttributes));
        $sanitized = Arr::except($attributes, $blocked);

        foreach ($sanitized as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $sanitized[$key] = json_decode(json_encode($value), true);
            }
        }

        return $sanitized;
    }
}
