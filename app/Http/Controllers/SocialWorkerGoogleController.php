<?php

namespace App\Http\Controllers;

use App\Services\GoogleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class SocialWorkerGoogleController extends Controller
{
    public function __construct(
        protected GoogleCalendarService $googleCalendar
    ) {
    }

    public function redirect(Request $request): RedirectResponse
    {
        abort_unless($this->canUseSocialWorkerTools($request), 403);

        if (! config('services.google.client_id') || ! config('services.google.client_secret') || ! config('services.google.redirect_uri')) {
            return redirect()
                ->route('profile.edit')
                ->with('error', 'Google Calendar is not configured yet. Please add the Google OAuth credentials first.');
        }

        $state = (string) Str::uuid();
        $request->session()->put('google_oauth_state', $state);

        return redirect()->away($this->googleCalendar->authorizationUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless($this->canUseSocialWorkerTools($request), 403);

        if ($request->has('error')) {
            return redirect()
                ->route('profile.edit')
                ->with('error', 'Google connection was cancelled.');
        }

        $expectedState = (string) $request->session()->pull('google_oauth_state');
        $receivedState = (string) $request->string('state');

        abort_unless(
            $expectedState !== '' && $receivedState !== '' && hash_equals($expectedState, $receivedState),
            403
        );

        try {
            $tokenPayload = $this->googleCalendar->exchangeCodeForTokens((string) $request->string('code'));
            $this->googleCalendar->connectUser($request->user(), $tokenPayload);

            return redirect()
                ->route('profile.edit')
                ->with('status', 'google-calendar-connected');
        } catch (Throwable $e) {
            return redirect()
                ->route('profile.edit')
                ->with('error', 'Google connection failed: '.$e->getMessage());
        }
    }

    public function disconnect(Request $request): RedirectResponse
    {
        abort_unless($this->canUseSocialWorkerTools($request), 403);

        try {
            $this->googleCalendar->disconnectUser($request->user());

            return redirect()
                ->route('profile.edit')
                ->with('status', 'google-calendar-disconnected');
        } catch (Throwable $e) {
            return redirect()
                ->route('profile.edit')
                ->with('error', 'Google disconnect failed: '.$e->getMessage());
        }
    }

    protected function canUseSocialWorkerTools(Request $request): bool
    {
        return (bool) $request->user()?->canAccessSocialWorkerModule();
    }
}
