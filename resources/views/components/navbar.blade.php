<header class="w-full sticky top-0 z-40 bg-white border-b border-slate-200 flex items-center justify-between px-8 py-4">

    <!-- LEFT -->
    <div class="flex items-center gap-8">

        <!-- TITLE -->
        <h1 class="text-lg font-bold text-sky-900">
            CrIServe Portal
        </h1>

        <!-- SEARCH -->
        <!--
        <div class="relative hidden md:block">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">
                search
            </span>

            <input
                type="text"
                placeholder="Search applications..."
                class="pl-10 pr-4 py-2 w-72 bg-slate-100 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition"
            >
        </div>
        -->

    </div>

    <!-- RIGHT -->
    <div class="flex items-center gap-5">

        @php
            $user = auth()->user();
            $email = $user->email ?? 'guest@example.com';
            $name = $user->name ?? explode('@', $email)[0];
            $initial = strtoupper(substr($email, 0, 1));
            $recentNotifications = $user?->notifications()->latest()->take(5)->get() ?? collect();
            $unreadNotifications = $user?->unreadNotifications()->count() ?? 0;
        @endphp

        <div class="relative" x-data="{ open: false }">
            <button type="button"
                    @click="open = !open"
                    class="relative flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 transition">
                <span class="material-symbols-outlined text-[20px]">notifications</span>

                @if($unreadNotifications > 0)
                    <span class="absolute -right-1 -top-1 min-w-[20px] rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                        {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
                    </span>
                @endif
            </button>

            <div x-cloak
                 x-show="open"
                 x-transition
                 @click.outside="open = false"
                 class="absolute right-0 mt-3 w-96 rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-800">Notifications</p>
                    <p class="text-xs text-slate-500">Latest updates on your applications</p>
                </div>

                <div class="max-h-96 overflow-y-auto">
                    @forelse($recentNotifications as $notification)
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="border-b border-slate-100 last:border-b-0">
                            @csrf
                            <button type="submit" class="w-full px-4 py-3 text-left hover:bg-slate-50 transition">
                                <div class="flex items-start gap-3">
                                    <span class="mt-1 h-2.5 w-2.5 rounded-full {{ $notification->read_at ? 'bg-slate-300' : 'bg-sky-500' }}"></span>

                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-slate-800">
                                            {{ $notification->data['title'] ?? 'Notification' }}
                                        </p>
                                        <p class="mt-1 text-sm text-slate-600">
                                            {{ $notification->data['message'] ?? '' }}
                                        </p>

                                        @if(!empty($notification->data['meeting_link']))
                                            <p class="mt-1 truncate text-xs text-sky-700">
                                                {{ $notification->data['meeting_link'] }}
                                            </p>
                                        @endif

                                        <p class="mt-2 text-xs text-slate-400">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </button>
                        </form>
                    @empty
                        <div class="px-4 py-8 text-center text-sm text-slate-500">
                            No notifications yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="relative" x-data="{ open: false }">
            <button type="button"
                    @click="open = !open"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-100 transition">

                <div class="text-right hidden sm:block leading-tight">
                    <p class="text-sm font-semibold text-slate-800">
                        {{ $name }}
                    </p>

                    <p class="text-xs text-slate-500">
                        {{ $email }}
                    </p>
                </div>

                <div class="w-9 h-9 rounded-full bg-sky-900 text-white flex items-center justify-center text-sm font-bold shadow-sm">
                    {{ $initial }}
                </div>

                <span class="material-symbols-outlined text-slate-500 text-[18px]">
                    expand_more
                </span>
            </button>

            <div x-cloak
                 x-show="open"
                 x-transition
                 @click.outside="open = false"
                 class="absolute right-0 mt-3 w-56 rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden">
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-3 px-4 py-3 text-sm text-slate-700 hover:bg-slate-50">
                    <span class="material-symbols-outlined text-[18px] text-slate-500">person</span>
                    Profile
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full text-left flex items-center gap-3 px-4 py-3 text-sm text-red-600 hover:bg-red-50">
                        <span class="material-symbols-outlined text-[18px]">logout</span>
                        Logout
                    </button>
                </form>
            </div>
        </div>

    </div>

</header>
