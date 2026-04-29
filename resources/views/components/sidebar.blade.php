<aside class="h-screen w-64 fixed left-0 top-0 overflow-y-auto bg-slate-50 flex flex-col border-r border-slate-200 z-50">

    @php
        $role = auth()->user()->role ?? 'client';

        $matchesPath = function ($paths) {
            foreach ((array) $paths as $path) {
                if (request()->is($path)) {
                    return true;
                }
            }

            return false;
        };

        $navClass = function ($path) use ($matchesPath) {
            return $matchesPath($path)
                ? 'bg-sky-50 text-sky-900 font-semibold border-r-4 border-sky-700 shadow-sm'
                : 'text-slate-600 hover:text-sky-700 hover:bg-slate-100';
        };

        $iconClass = function ($path) use ($matchesPath) {
            return $matchesPath($path)
                ? 'text-sky-700'
                : 'text-slate-500 group-hover:text-sky-700';
        };
    @endphp

    <!-- LOGO -->
    <div class="px-6 py-8 flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-primary-container flex items-center justify-center shadow-sm">
            <span class="material-symbols-outlined text-on-primary-container">security</span>
        </div>
        <div>
            <h2 class="text-lg font-black text-sky-900 leading-tight">CrIServe</h2>
            <p class="text-[10px] uppercase tracking-widest text-on-surface-variant font-bold">
                Crisis Management
            </p>
        </div>
    </div>

    <!-- NAV -->
    <nav class="flex-1 px-4 space-y-1">

        <!-- ================= CLIENT ================= -->
        @if($role === 'client')

        <a href="/client/dashboard"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('client/dashboard') }}">

            <span class="material-symbols-outlined {{ $iconClass('client/dashboard') }}">
                dashboard
            </span>

            <span class="text-sm tracking-wide">Dashboard</span>
        </a>

        <a href="/client/applications"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass(['client/applications*', 'client/application/*']) }}">

            <span class="material-symbols-outlined {{ $iconClass(['client/applications*', 'client/application/*']) }}">
                folder_open
            </span>

            <span class="text-sm tracking-wide">Applications</span>
        </a>

        <a href="/client/family"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('client/family*') }}">

            <span class="material-symbols-outlined {{ $iconClass('client/family*') }}">
                family_restroom
            </span>

            <span class="text-sm tracking-wide">My Family</span>
        </a>

        <a href="/client/application"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('client/application') }}">

            <span class="material-symbols-outlined {{ $iconClass('client/application') }}">
                description
            </span>

            <span class="text-sm tracking-wide">Apply for Assistance</span>
        </a>

        @endif


        <!-- ================= SOCIAL WORKER ================= -->
        @if($role === 'social_worker')

        <a href="/social-worker/dashboard"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('social-worker/dashboard') }}">

            <span class="material-symbols-outlined {{ $iconClass('social-worker/dashboard') }}">
                dashboard
            </span>

            <span class="text-sm tracking-wide">Dashboard</span>
        </a>

        <a href="/social-worker/applications"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('social-worker/applications*') }}">

            <span class="material-symbols-outlined {{ $iconClass('social-worker/applications*') }}">
                description
            </span>

            <span class="text-sm tracking-wide">Applications</span>
        </a>

        <a href="/social-worker/my-cases"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('social-worker/my-cases*') }}">

            <span class="material-symbols-outlined {{ $iconClass('social-worker/my-cases*') }}">
                assignment_ind
            </span>

            <span class="text-sm tracking-wide">My Cases</span>
        </a>

        <a href="/social-worker/schedule"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('social-worker/schedule*') }}">

            <span class="material-symbols-outlined {{ $iconClass('social-worker/schedule*') }}">
                calendar_month
            </span>

            <span class="text-sm tracking-wide">My Schedule</span>
        </a>

        @endif


        <!-- ================= ADMIN ================= -->
        @if($role === 'admin')

        <a href="/admin/dashboard"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('admin/dashboard') }}">

            <span class="material-symbols-outlined {{ $iconClass('admin/dashboard') }}">
                admin_panel_settings
            </span>

            <span class="text-sm tracking-wide">Dashboard</span>
        </a>

        @php
            $libraryLinks = [
                ['label' => 'Assistance Types', 'path' => 'admin/libraries/assistance-types'],
                ['label' => 'Assistance Subtypes', 'path' => 'admin/libraries/assistance-subtypes'],
                ['label' => 'Assistance Details', 'path' => 'admin/libraries/assistance-details'],
                ['label' => 'Modes of Assistance', 'path' => 'admin/libraries/modes-of-assistance'],
                ['label' => 'Relationships', 'path' => 'admin/libraries/relationships'],
                ['label' => 'Referral Institutions', 'path' => 'admin/libraries/referral-institutions'],
                ['label' => 'Frequency Rules', 'path' => 'admin/frequency-rules'],
            ];
            $librariesOpen = request()->is('admin/libraries*') || request()->is('admin/frequency-rules*');
        @endphp

        <details class="group" @if($librariesOpen) open @endif>
            <summary class="list-none group flex items-center justify-between gap-3 px-4 py-3 rounded-lg cursor-pointer transition-all duration-200 {{ $navClass(['admin/libraries*', 'admin/frequency-rules*']) }}">
                <span class="flex items-center gap-3">
                    <span class="material-symbols-outlined {{ $iconClass(['admin/libraries*', 'admin/frequency-rules*']) }}">
                        library_books
                    </span>
                    <span class="text-sm tracking-wide">Libraries</span>
                </span>

                <span class="material-symbols-outlined text-[18px] text-slate-400 transition-transform duration-200 group-open:rotate-180">
                    expand_more
                </span>
            </summary>

            <div class="ml-4 mt-1 space-y-1">
                @foreach($libraryLinks as $libraryLink)
                    <a href="/{{ $libraryLink['path'] }}"
                       class="group flex items-center gap-3 px-4 py-2 rounded-lg text-sm transition-all duration-200 {{ $navClass($libraryLink['path']) }}">
                        <span class="h-2 w-2 rounded-full {{ request()->is($libraryLink['path']) ? 'bg-sky-700' : 'bg-slate-300 group-hover:bg-sky-500' }}"></span>
                        <span>{{ $libraryLink['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </details>

        <a href="/admin/users"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('admin/users*') }}">

            <span class="material-symbols-outlined {{ $iconClass('admin/users*') }}">
                manage_accounts
            </span>

            <span class="text-sm tracking-wide">User Management</span>
        </a>

        <a href="/admin/support-tickets"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('admin/support-tickets*') }}">

            <span class="material-symbols-outlined {{ $iconClass('admin/support-tickets*') }}">
                support_agent
            </span>

            <span class="text-sm tracking-wide">Support Tickets</span>
        </a>

        <a href="/social-worker/applications"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-slate-600 hover:text-sky-700 hover:bg-slate-100">

            <span class="material-symbols-outlined text-slate-500 group-hover:text-sky-700">
                description
            </span>

            <span class="text-sm tracking-wide">All Applications</span>
        </a>

        <a href="/approving-officer/applications"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-slate-600 hover:text-sky-700 hover:bg-slate-100">

            <span class="material-symbols-outlined text-slate-500 group-hover:text-sky-700">
                fact_check
            </span>

            <span class="text-sm tracking-wide">Approvals Queue</span>
        </a>

        @endif

        <!-- APPROVING OFFICER -->
        @if($role === 'approving_officer')

            <!-- DASHBOARD -->
            <a href="/approving-officer/dashboard"
            class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('approving-officer/dashboard') }}">

                <span class="material-symbols-outlined {{ $iconClass('approving-officer/dashboard') }}">
                    dashboard
                </span>

                <span class="text-sm tracking-wide">
                    Dashboard
                </span>
            </a>

            <!-- APPLICATIONS -->
            <a href="/approving-officer/applications"
            class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('approving-officer/applications*') }}">

                <span class="material-symbols-outlined {{ $iconClass('approving-officer/applications*') }}">
                    fact_check
                </span>

                <span class="text-sm tracking-wide">
                    Approvals
                </span>
            </a>

            <a href="/approving-officer/my-approvals"
            class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ $navClass('approving-officer/my-approvals*') }}">

                <span class="material-symbols-outlined {{ $iconClass('approving-officer/my-approvals*') }}">
                    approval_delegation
                </span>

                <span class="text-sm tracking-wide">
                    My Approvals
                </span>
            </a>

        @endif
    </nav>

</aside>
