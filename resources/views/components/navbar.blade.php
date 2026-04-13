<header class="w-full sticky top-0 z-40 bg-white border-b border-slate-200 flex items-center justify-between px-8 py-4">

    <!-- LEFT -->
    <div class="flex items-center gap-8">

        <!-- TITLE -->
        <h1 class="text-lg font-bold text-sky-900">
            CrIServe Portal
        </h1>

        <!-- SEARCH -->
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

    </div>

    <!-- RIGHT -->
    <div class="flex items-center gap-5">

        <!-- NOTIFICATIONS -->
        <button class="relative p-2 rounded-full hover:bg-slate-100 transition">
            <span class="material-symbols-outlined text-slate-600">
                notifications
            </span>

            <!-- NOTIF DOT -->
            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full"></span>
        </button>

        <!-- USER -->
        <div class="flex items-center gap-3 cursor-pointer hover:bg-slate-100 px-2 py-1 rounded-lg transition">
            <span class="text-sm font-medium text-slate-700">User</span>

            <img 
                src="https://i.pravatar.cc/40"
                class="w-8 h-8 rounded-full border border-slate-200"
            >
        </div>

    </div>

</header>