<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleCalendarService
{
    public function authorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect_uri'),
            'response_type' => 'code',
            'scope' => implode(' ', config('services.google.scopes', [])),
            'access_type' => 'offline',
            'include_granted_scopes' => 'true',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return rtrim((string) config('services.google.auth_url'), '?').'?'.$query;
    }

    public function exchangeCodeForTokens(string $code): array
    {
        return $this->tokenRequest([
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('services.google.redirect_uri'),
        ]);
    }

    public function connectUser(User $user, array $tokenPayload): void
    {
        $accessToken = $tokenPayload['access_token'] ?? null;
        $refreshToken = $tokenPayload['refresh_token'] ?? null;

        if (! $accessToken || ! $refreshToken) {
            throw new RuntimeException('Google did not return the required access and refresh tokens.');
        }

        $userInfo = Http::withToken($accessToken)
            ->acceptJson()
            ->get('https://openidconnect.googleapis.com/v1/userinfo')
            ->throw()
            ->json();

        $user->forceFill([
            'google_email' => $userInfo['email'] ?? null,
            'google_access_token' => $accessToken,
            'google_refresh_token' => $refreshToken,
            'google_token_expires_at' => now()->addSeconds((int) ($tokenPayload['expires_in'] ?? 3600)),
            'google_calendar_connected_at' => now(),
        ])->save();
    }

    public function disconnectUser(User $user): void
    {
        if ($user->google_refresh_token) {
            $response = Http::asForm()->post(config('services.google.revoke_url'), [
                'token' => $user->google_refresh_token,
            ]);

            if ($response->failed() && $response->status() !== 400) {
                $response->throw();
            }
        }

        $user->forceFill([
            'google_email' => null,
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
            'google_calendar_connected_at' => null,
        ])->save();
    }

    public function syncAssessmentSchedule(User $user, Application $application, ?string $scheduledAt, ?string $notes = null): ?array
    {
        if (! $user->hasGoogleCalendarConnection()) {
            return null;
        }

        if (! $scheduledAt) {
            if ($application->google_calendar_event_id) {
                $this->deleteEvent($user, $application->google_calendar_event_id);

                return [
                    'meeting_link' => null,
                    'google_calendar_event_id' => null,
                    'google_calendar_event_link' => null,
                ];
            }

            return null;
        }

        $start = Carbon::parse($scheduledAt, config('app.timezone'));
        $end = (clone $start)->addMinutes((int) config('services.google.default_event_duration', 60));

        $payload = [
            'summary' => 'Initial Assessment - '.$application->reference_no,
            'description' => $this->buildDescription($application, $notes),
            'start' => [
                'dateTime' => $start->toAtomString(),
                'timeZone' => config('app.timezone'),
            ],
            'end' => [
                'dateTime' => $end->toAtomString(),
                'timeZone' => config('app.timezone'),
            ],
            'extendedProperties' => [
                'private' => [
                    'criserve_application_id' => (string) $application->id,
                    'criserve_reference_no' => (string) $application->reference_no,
                ],
            ],
        ];

        if (! $application->google_calendar_event_id) {
            $payload['conferenceData'] = [
                'createRequest' => [
                    'conferenceSolutionKey' => [
                        'type' => 'hangoutsMeet',
                    ],
                    'requestId' => (string) Str::uuid(),
                ],
            ];
        }

        $endpoint = $application->google_calendar_event_id
            ? $this->calendarBaseUrl().'/calendars/primary/events/'.$application->google_calendar_event_id
            : $this->calendarBaseUrl().'/calendars/primary/events';

        $response = $this->calendarRequest(
            $user,
            $application->google_calendar_event_id ? 'patch' : 'post',
            $endpoint,
            [
                'conferenceDataVersion' => 1,
            ],
            $payload
        )->json();

        return [
            'meeting_link' => $this->extractMeetingLink($response),
            'google_calendar_event_id' => $response['id'] ?? null,
            'google_calendar_event_link' => $response['htmlLink'] ?? null,
        ];
    }

    protected function buildDescription(Application $application, ?string $notes = null): string
    {
        $client = $application->client;
        $clientName = trim(implode(' ', array_filter([
            $client?->first_name,
            $client?->middle_name,
            $client?->last_name,
            $client?->extension_name,
        ])));

        return trim(implode("\n", array_filter([
            'CrIServe initial assessment schedule',
            'Reference No: '.$application->reference_no,
            $clientName ? 'Client: '.$clientName : null,
            $application->assistanceType?->name ? 'Assistance Type: '.$application->assistanceType->name : null,
            $application->assistanceSubtype?->name ? 'Specific Assistance: '.$application->assistanceSubtype->name : null,
            $notes ? 'Notes: '.trim($notes) : null,
        ])));
    }

    protected function extractMeetingLink(array $event): ?string
    {
        if (! empty($event['hangoutLink'])) {
            return $event['hangoutLink'];
        }

        foreach (($event['conferenceData']['entryPoints'] ?? []) as $entryPoint) {
            if (($entryPoint['entryPointType'] ?? null) === 'video' && ! empty($entryPoint['uri'])) {
                return $entryPoint['uri'];
            }
        }

        return null;
    }

    protected function deleteEvent(User $user, string $eventId): void
    {
        try {
            $this->calendarRequest(
                $user,
                'delete',
                $this->calendarBaseUrl().'/calendars/primary/events/'.$eventId
            );
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) !== 404) {
                throw $e;
            }
        }
    }

    protected function calendarRequest(
        User $user,
        string $method,
        string $url,
        array $query = [],
        array $payload = []
    ) {
        return $this->requestWithRefresh($user, function (string $token) use ($method, $url, $query, $payload) {
            return Http::withToken($token)
                ->acceptJson()
                ->send($method, $url, [
                    'query' => $query,
                    'json' => $payload,
                ])
                ->throw();
        });
    }

    protected function requestWithRefresh(User $user, callable $callback)
    {
        $accessToken = $this->accessTokenFor($user);

        try {
            return $callback($accessToken);
        } catch (RequestException $e) {
            if (($e->response?->status() ?? 0) !== 401 || ! $user->google_refresh_token) {
                throw $e;
            }

            $accessToken = $this->refreshAccessToken($user);

            return $callback($accessToken);
        }
    }

    protected function accessTokenFor(User $user): string
    {
        if (! $user->google_access_token || ! $user->google_token_expires_at || $user->google_token_expires_at->lte(now()->addMinute())) {
            return $this->refreshAccessToken($user);
        }

        return $user->google_access_token;
    }

    protected function refreshAccessToken(User $user): string
    {
        if (! $user->google_refresh_token) {
            throw new RuntimeException('Google Calendar is not connected for this social worker.');
        }

        $tokenPayload = $this->tokenRequest([
            'grant_type' => 'refresh_token',
            'refresh_token' => $user->google_refresh_token,
        ]);

        $user->forceFill([
            'google_access_token' => $tokenPayload['access_token'] ?? null,
            'google_token_expires_at' => now()->addSeconds((int) ($tokenPayload['expires_in'] ?? 3600)),
        ])->save();

        return (string) $user->google_access_token;
    }

    protected function tokenRequest(array $payload): array
    {
        return Http::asForm()
            ->acceptJson()
            ->post(config('services.google.token_url'), array_merge($payload, [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
            ]))
            ->throw()
            ->json();
    }

    protected function calendarBaseUrl(): string
    {
        return rtrim((string) config('services.google.calendar_base_url'), '/');
    }
}
