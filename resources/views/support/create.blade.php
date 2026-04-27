<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Support | CrIServe Portal</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<style>
body { font-family: 'Inter', sans-serif; }

.input {
    width: 100%;
    padding: 12px;
    background: #f1f5f9;
    border-radius: 10px;
    border: 2px solid transparent;
}
.input:focus {
    outline: none;
    border-color: #0B3C5D;
    background: white;
}
</style>
</head>

<body class="bg-slate-100">
<header class="flex justify-between items-center px-8 py-4 bg-white shadow-sm">
    <h1 class="font-bold text-[#0B3C5D]">CrIServe Portal</h1>

    <div class="flex items-center gap-3">
        <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Sign In
        </a>
        <a href="{{ route('register') }}" class="rounded-lg bg-[#0B3C5D] px-4 py-2 text-sm font-semibold text-white hover:opacity-90">
            Register
        </a>
    </div>
</header>

<div class="mx-auto mt-10 grid max-w-6xl grid-cols-12 gap-8 px-6">
    <div class="col-span-4 rounded-2xl bg-gradient-to-br from-[#0B3C5D] to-[#174A6B] p-8 text-white shadow-lg">
        <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em]">
            Support Desk
        </span>
        <h2 class="mt-4 text-3xl font-black leading-tight">Submit an account recovery or support request.</h2>
        <p class="mt-4 text-sm leading-6 text-blue-100">
            Use this form if you lost access to your registered email, need account recovery help, or have a support concern that cannot be resolved through sign in or password reset.
        </p>

        <div class="mt-8 space-y-3 text-sm text-blue-50">
            <p>- One request is enough. Repeated tickets are automatically limited.</p>
            <p>- Include enough detail so the administrator can verify your concern.</p>
            <p>- For account recovery, use the same name and birthdate used during registration.</p>
        </div>
    </div>

    <div class="col-span-8 rounded-2xl bg-white p-8 shadow-lg">
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
            <p class="font-bold">Anti-spam protection is active.</p>
            <p class="mt-1">
                Repeated submissions from the same email or connection are limited, and duplicate recent messages are blocked automatically.
            </p>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-xl border border-red-300 bg-red-100 px-4 py-4 text-sm text-red-700">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                        <li>- {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-8">
            <span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Support Form</span>
            <h3 class="mt-2 text-2xl font-black text-[#0B3C5D]">Tell us what you need help with</h3>
            <p class="mt-2 text-sm text-slate-500">
                Your request will be stored for administrator review. Please avoid sending the same concern multiple times.
            </p>
        </div>

        <form method="POST" action="{{ route('support.store') }}" class="space-y-6">
            @csrf

            <input type="hidden" name="source" value="{{ $source }}">

            <div class="hidden">
                <label for="website">Website</label>
                <input id="website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="name" class="text-sm font-semibold text-slate-600">Full Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name', $prefillName) }}" class="input mt-2" required>
                </div>

                <div>
                    <label for="email" class="text-sm font-semibold text-slate-600">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email', $prefillEmail) }}" class="input mt-2" required>
                </div>
            </div>

            <div>
                <label for="subject" class="text-sm font-semibold text-slate-600">Subject</label>
                <input id="subject" type="text" name="subject" value="{{ old('subject', $source === 'account-recovery' ? 'Account recovery request' : '') }}" class="input mt-2" required>
            </div>

            <div>
                <label for="message" class="text-sm font-semibold text-slate-600">Message</label>
                <textarea id="message" name="message" rows="8" class="input mt-2 resize-none" required placeholder="Describe your concern, include your full identity details if you need account recovery, and explain what access problem you are experiencing.">{{ old('message') }}</textarea>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t pt-6">
                <div class="text-sm text-slate-500">
                    Prefer self-service?
                    <a href="{{ route('password.request') }}" class="font-semibold text-[#0B3C5D] hover:underline">Try Forgot Password first</a>
                </div>

                <button type="submit" class="rounded-lg bg-gradient-to-r from-[#0B3C5D] to-[#174A6B] px-6 py-3 font-semibold text-white shadow hover:opacity-90">
                    Submit Support Ticket
                </button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
