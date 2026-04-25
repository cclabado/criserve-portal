<section class="space-y-5">
    <header>
        <h2 class="text-lg font-medium text-gray-900">Google Calendar & Meet</h2>
        <p class="mt-1 text-sm text-gray-600">
            Connect your Google account once so scheduled initial assessments automatically create a Google Calendar event and a Google Meet link.
        </p>
    </header>

    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 space-y-4">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold text-slate-800">
                    @if($user->hasGoogleCalendarConnection())
                        Connected to {{ $user->google_email ?: 'your Google account' }}
                    @else
                        Google Calendar is not connected yet
                    @endif
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    @if($user->hasGoogleCalendarConnection())
                        Connected on {{ optional($user->google_calendar_connected_at)->format('M d, Y h:i A') }}. New schedules will auto-create a Meet link.
                    @else
                        After connecting, saving a schedule in Initial Assessment will create the calendar event for your account automatically.
                    @endif
                </p>
            </div>

            @if($user->hasGoogleCalendarConnection())
                <form method="post" action="{{ route('socialworker.google.disconnect') }}">
                    @csrf
                    @method('delete')

                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50">
                        Disconnect Google
                    </button>
                </form>
            @else
                <a href="{{ route('socialworker.google.connect') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-sky-900 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-800">
                    Connect Google Account
                </a>
            @endif
        </div>

        @if(session('status') === 'google-calendar-connected')
            <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                Google Calendar was connected successfully.
            </p>
        @endif

        @if(session('status') === 'google-calendar-disconnected')
            <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                Google Calendar was disconnected.
            </p>
        @endif

        @if(session('error'))
            <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </p>
        @endif
    </div>
</section>
