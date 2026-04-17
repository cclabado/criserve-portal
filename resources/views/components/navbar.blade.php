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
        @endphp

        <!-- USER -->
        <div class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-100 transition">

            <!-- USER INFO -->
            <div class="text-right hidden sm:block leading-tight">
                <p class="text-sm font-semibold text-slate-800">
                    {{ $name }}
                </p>

                <p class="text-xs text-slate-500">
                    {{ $email }}
                </p>
            </div>

            <!-- AVATAR -->
            <div class="w-9 h-9 rounded-full bg-sky-900 text-white flex items-center justify-center text-sm font-bold shadow-sm">
                {{ $initial }}
            </div>

        </div>

    </div>

</header>