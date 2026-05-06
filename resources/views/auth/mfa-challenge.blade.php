<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CriServe Portal - Security Verification</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
body { font-family: 'Inter', sans-serif; }
.bg-gradient-auth { background: linear-gradient(135deg, #003857 0%, #1b4f72 100%); }
</style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">
    <main class="w-full max-w-md rounded-xl border bg-white p-8 shadow">
        <h1 class="text-2xl font-bold text-gray-900">Security Verification</h1>
        <p class="mt-2 text-sm text-gray-600">Enter the verification code sent to <span class="font-semibold">{{ $email }}</span>.</p>

        <x-auth-session-status class="mt-4" :status="session('status')" />

        <form method="POST" action="{{ route('mfa.challenge.store') }}" class="mt-6 space-y-5">
            @csrf
            <div>
                <label class="text-sm font-semibold text-gray-600">Verification Code</label>
                <input type="text"
                       name="code"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       maxlength="{{ max(6, (int) config('security.mfa.code_length', 6)) }}"
                       required
                       class="mt-2 w-full rounded-lg border border-slate-200 bg-gray-50 px-4 py-3 tracking-[0.3em] outline-none focus:border-blue-900 focus:bg-white">
                <x-input-error :messages="$errors->get('code')" class="mt-1" />
            </div>

            <button type="submit" class="w-full rounded-lg bg-gradient-auth py-3 font-bold text-white hover:opacity-90">
                Verify and Continue
            </button>
        </form>

        <form method="POST" action="{{ route('mfa.challenge.resend') }}" class="mt-4">
            @csrf
            <button type="submit" class="text-sm font-semibold text-blue-700 hover:underline">Send a new code</button>
        </form>
    </main>
</body>
</html>
