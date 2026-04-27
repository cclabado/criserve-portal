<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | CrIServe Portal</title>

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

    <div class="flex items-center gap-4">
        <span class="text-sm text-slate-600">Remembered your account?</span>
        <a href="{{ route('login') }}" class="rounded-lg bg-[#0B3C5D] px-4 py-2 font-semibold text-white hover:opacity-90">
            Sign In
        </a>
    </div>
</header>

<div class="mx-auto mt-10 grid max-w-6xl grid-cols-12 gap-8 px-6">

    <div class="col-span-4 rounded-2xl bg-gradient-to-br from-[#0B3C5D] to-[#174A6B] p-8 text-white shadow-lg">
        <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.2em]">
            Account Recovery
        </span>
        <h2 class="mt-4 text-3xl font-black leading-tight">Reset your password securely.</h2>
        <p class="mt-4 text-sm leading-6 text-blue-100">
            Enter the email address tied to your client account and we’ll send a password reset link so you can recover access.
        </p>

        <div class="mt-8 space-y-3 text-sm text-blue-50">
            <p>- Use the same email you registered with.</p>
            <p>- Check your inbox and spam folder after sending the request.</p>
            <p>- If you no longer have access to that email, contact support for manual recovery.</p>
        </div>
    </div>

    <div class="col-span-8 rounded-2xl bg-white p-8 shadow-lg">
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
            <p class="font-bold">Password reset works only for your registered email.</p>
            <p class="mt-1">
                If you lost access to that email address, please contact support so your account can be reviewed for recovery assistance.
            </p>
            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('login') }}" class="inline-flex items-center rounded-lg bg-[#0B3C5D] px-4 py-2 text-xs font-bold text-white hover:opacity-90">
                    Back to Sign In
                </a>
                <a href="{{ route('support.create', ['source' => 'account-recovery']) }}" class="inline-flex items-center rounded-lg border border-amber-300 bg-white px-4 py-2 text-xs font-bold text-amber-900 hover:bg-amber-100">
                    Contact Support
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-300 bg-red-100 px-4 py-4 text-sm text-red-700">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>- {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-8">
            <span class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Step 1</span>
            <h3 class="mt-2 text-2xl font-black text-[#0B3C5D]">Send Password Reset Link</h3>
            <p class="mt-2 text-sm text-slate-500">
                We’ll email a secure recovery link to the address connected to your CrIServe account.
            </p>
        </div>

        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf

            <div>
                <label for="email" class="text-sm font-semibold text-slate-600">Registered Email Address</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" class="input mt-2" required autofocus placeholder="you@example.com">
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t pt-6">
                <div class="text-sm text-slate-500">
                    Need a different recovery path?
                    <a href="{{ route('support.create', ['source' => 'account-recovery']) }}" class="font-semibold text-[#0B3C5D] hover:underline">Contact Support</a>
                </div>

                <button type="submit" class="rounded-lg bg-gradient-to-r from-[#0B3C5D] to-[#174A6B] px-6 py-3 font-semibold text-white shadow hover:opacity-90">
                    Email Password Reset Link
                </button>
            </div>
        </form>
    </div>

</div>
</body>
</html>
