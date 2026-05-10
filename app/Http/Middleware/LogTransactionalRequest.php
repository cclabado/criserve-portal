<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogTransactionalRequest
{
    public function __construct(
        protected AuditLogService $auditLogs
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldLog($request, $response)) {
            return $response;
        }

        $route = $request->route();
        $routeName = $route?->getName() ?: 'unnamed';

        $this->auditLogs->log($request, 'request.transaction', null, [
            'method' => $request->method(),
            'path' => $request->path(),
            'route_name' => $routeName,
            'status_code' => $response->getStatusCode(),
        ]);

        return $response;
    }

    protected function shouldLog(Request $request, Response $response): bool
    {
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        if ($response->getStatusCode() >= 400) {
            return false;
        }

        if ($request->routeIs('notifications.read')) {
            return false;
        }

        return true;
    }
}
