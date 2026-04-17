<aside class="h-screen w-64 fixed left-0 top-0 overflow-y-auto bg-slate-50 flex flex-col border-r border-slate-200 z-50">

    @php
        $role = auth()->user()->role ?? 'client';

        function navClass($path) {
            return request()->is($path)
                ? 'bg-sky-50 text-sky-900 font-semibold border-r-4 border-sky-700 shadow-sm'
                : 'text-slate-600 hover:text-sky-700 hover:bg-slate-100';
        }

        function iconClass($path) {
            return request()->is($path)
                ? 'text-sky-700'
                : 'text-slate-500 group-hover:text-sky-700';
        }
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
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ navClass('client/dashboard') }}">

            <span class="material-symbols-outlined {{ iconClass('client/dashboard') }}">
                dashboard
            </span>

            <span class="text-sm tracking-wide">Dashboard</span>
        </a>

        <a href="/client/application"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ navClass('client/application*') }}">

            <span class="material-symbols-outlined {{ iconClass('client/application*') }}">
                description
            </span>

            <span class="text-sm tracking-wide">Apply for Assistance</span>
        </a>

        @endif


        <!-- ================= SOCIAL WORKER ================= -->
        @if($role === 'social_worker')

        <a href="/social-worker/dashboard"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ navClass('social-worker/dashboard') }}">

            <span class="material-symbols-outlined {{ iconClass('social-worker/dashboard') }}">
                dashboard
            </span>

            <span class="text-sm tracking-wide">Dashboard</span>
        </a>

        <a href="/social-worker/applications"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ navClass('social-worker/applications*') }}">

            <span class="material-symbols-outlined {{ iconClass('social-worker/applications*') }}">
                description
            </span>

            <span class="text-sm tracking-wide">Applications</span>
        </a>

        @endif


        <!-- ================= ADMIN ================= -->
        @if($role === 'admin')

        <a href="/admin/applications"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ navClass('admin/applications*') }}">

            <span class="material-symbols-outlined {{ iconClass('admin/applications*') }}">
                description
            </span>

            <span class="text-sm tracking-wide">Applications</span>
        </a>

        <a href="/admin/approvals"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ navClass('admin/approvals*') }}">

            <span class="material-symbols-outlined {{ iconClass('admin/approvals*') }}">
                fact_check
            </span>

            <span class="text-sm tracking-wide">Approvals</span>
        </a>

        <a href="/admin/release"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ navClass('admin/release*') }}">

            <span class="material-symbols-outlined {{ iconClass('admin/release*') }}">
                inventory_2
            </span>

            <span class="text-sm tracking-wide">Release</span>
        </a>

        @endif

        <!-- APPROVING OFFICER -->
        @if($role === 'approving_officer')

            <!-- DASHBOARD -->
            <a href="/approving-officer/dashboard"
            class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ navClass('approving-officer/dashboard') }}">

                <span class="material-symbols-outlined {{ iconClass('approving-officer/dashboard') }}">
                    dashboard
                </span>

                <span class="text-sm tracking-wide">
                    Dashboard
                </span>
            </a>

            <!-- APPLICATIONS -->
            <a href="/approving-officer/applications"
            class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 {{ navClass('approving-officer/applications*') }}">

                <span class="material-symbols-outlined {{ iconClass('approving-officer/applications*') }}">
                    fact_check
                </span>

                <span class="text-sm tracking-wide">
                    Approvals
                </span>
            </a>

        @endif
    </nav>

    <!-- FOOTER -->
    <div class="px-4 py-6 border-t border-slate-200 space-y-1">

        <!-- <a href="#"
           class="group flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-slate-600 hover:text-sky-700 hover:bg-slate-100">

            <span class="material-symbols-outlined group-hover:text-sky-700">
                settings
            </span>

            <span class="text-sm tracking-wide">Settings</span>
        </a> -->

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="group w-full text-left flex items-center gap-3 px-4 py-3 rounded-lg transition-all duration-200 text-slate-600 hover:text-red-500 hover:bg-red-50">

                <span class="material-symbols-outlined group-hover:text-red-500">
                    logout
                </span>

                <span class="text-sm tracking-wide">Logout</span>
            </button>
        </form>

    </div>

</aside>