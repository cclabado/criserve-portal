<?php

namespace App\Http\Controllers;

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

        return redirect($notification->data['route'] ?? route('client.dashboard'));
    }
}
