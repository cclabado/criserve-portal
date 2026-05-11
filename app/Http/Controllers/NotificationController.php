<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogs
    ) {
    }

    public function markAsRead(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();

        if (! $notification->read_at) {
            $notification->markAsRead();
            $this->auditLogs->log($request, 'notification.read', null, [
                'notification_id' => $notification->id,
                'notification_type' => $notification->type,
                'notification_route' => $notification->data['route'] ?? null,
            ]);
        }

        return redirect($this->resolveNotificationRoute($request, $notification->data['route'] ?? null));
    }

    protected function resolveNotificationRoute(Request $request, ?string $route): string
    {
        $fallback = $this->defaultDashboardRoute($request->user());

        if (blank($route)) {
            return $fallback;
        }

        $path = parse_url($route, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return $fallback;
        }

        $normalizedPath = ltrim($path, '/');

        if ($normalizedPath === '') {
            return $fallback;
        }

        if (! $request->user()?->canAccessSocialWorkerModule() && str_starts_with($normalizedPath, 'social-worker/')) {
            return $fallback;
        }

        if ($request->user()?->role !== 'admin' && str_starts_with($normalizedPath, 'admin/')) {
            return $fallback;
        }

        if ($request->user()?->role !== 'admin' && $request->user()?->role !== 'reporting_officer' && str_starts_with($normalizedPath, 'reporting-officer/')) {
            return $fallback;
        }

        if ($request->user()?->role !== 'admin' && $request->user()?->role !== 'approving_officer' && str_starts_with($normalizedPath, 'approving-officer/')) {
            return $fallback;
        }

        if ($request->user()?->role !== 'admin' && $request->user()?->role !== 'service_provider' && str_starts_with($normalizedPath, 'service-provider/')) {
            return $fallback;
        }

        if ($request->user()?->role !== 'admin' && $request->user()?->role !== 'gl_payment_processor' && str_starts_with($normalizedPath, 'gl-payment-processor/')) {
            return $fallback;
        }

        if ($request->user()?->role !== 'admin' && $request->user()?->role !== 'referral_institution' && str_starts_with($normalizedPath, 'referral-institution/')) {
            return $fallback;
        }

        if ($request->user()?->role !== 'admin' && $request->user()?->role !== 'referral_officer' && str_starts_with($normalizedPath, 'referral-officer/')) {
            return $fallback;
        }

        if ($request->user()?->role !== 'admin' && $request->user()?->role !== 'client' && str_starts_with($normalizedPath, 'client/')) {
            return $fallback;
        }

        return $route;
    }

    protected function defaultDashboardRoute(?User $user): string
    {
        if (! $user) {
            return route('dashboard');
        }

        if ($user->role === 'admin') {
            return route('admin.dashboard');
        }

        if ($user->role === 'reporting_officer') {
            return route('reporting.dashboard');
        }

        if ($user->role === 'social_worker') {
            return route('socialworker.applications');
        }

        if ($user->role === 'approving_officer') {
            return route('approving.dashboard');
        }

        if ($user->role === 'service_provider') {
            return route('service-provider.dashboard');
        }

        if ($user->role === 'gl_payment_processor') {
            return route('gl-payment-processor.dashboard');
        }

        if ($user->role === 'referral_institution') {
            return route('referral-institution.dashboard');
        }

        if ($user->role === 'referral_officer') {
            return route('referral-officer.dashboard');
        }

        return route('client.dashboard');
    }
}
