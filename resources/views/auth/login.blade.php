<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CrIServe Portal - Login</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">

<style>
body { font-family: 'Inter', sans-serif; }

.bg-gradient-auth {
    background: linear-gradient(135deg, #003857 0%, #1b4f72 100%);
}
</style>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">

<main class="flex-grow flex items-center justify-center p-6">

<div class="w-full max-w-md">

<!-- HEADER -->
<div class="text-center mb-10">

<div class="flex justify-center items-center gap-4 mb-6">

<div class="h-14 w-14 bg-white rounded-lg p-2 shadow border">
<img src="https://lh3.googleusercontent.com/aida-public/AB6AXuB3ujj8c_HqwMebVUtneHf_rxveLiJa_sKq_AvFgR3AfWnm46mQ5cobmptpVEF8fMiWmvwi7Rz1HhPJZLuKIJEtzxJ2KIRyivMIuN9QUfUDGWVaEGW8pRdo-c5t_pPNaVHR7yJyPnLuqTX_H-5lkKm1FeEP2Z0IG4zqGw1V-7CqDEvEb-Ot88rD84QbYXZzrNGRBYXS_vGfgixlcVXK4Mx30sJR0NM8qAcL0qsED_MSRE4B4es9YoL48p3R_Agy2NYbHdV85BVn2S8"
     class="h-full w-full object-contain">
</div>

<div class="h-10 w-[2px] bg-gray-300"></div>

<div class="text-left">
<h1 class="font-extrabold text-2xl text-blue-900 leading-none">CrIServe</h1>
<p class="text-xs uppercase tracking-widest text-gray-500 mt-1">
Crisis Management Portal
</p>
</div>

</div>

<h2 class="text-3xl font-bold text-gray-900">Welcome back</h2>
<p class="text-gray-500 mt-2">Enter your credentials to access the portal</p>

</div>

<!-- CARD -->
<div class="bg-white rounded-xl p-8 shadow border">

<!-- SESSION -->
<x-auth-session-status class="mb-4" :status="session('status')" />

<form method="POST" action="{{ route('login') }}" class="space-y-6">
@csrf

<!-- EMAIL -->
<div>
<label class="text-sm font-semibold text-gray-600 ml-1">Email Address</label>

<div class="relative mt-2">
<span class="absolute left-3 top-3 text-gray-400 material-symbols-outlined">mail</span>

<input type="email" name="email" value="{{ old('email') }}" required
class="w-full pl-10 pr-4 py-3 bg-gray-100 rounded-lg focus:bg-white border-b-2 border-transparent focus:border-blue-900 outline-none">
</div>

<x-input-error :messages="$errors->get('email')" class="mt-1" />
</div>

<!-- PASSWORD -->
<div>
<div class="flex justify-between text-sm">
<label class="font-semibold text-gray-600">Password</label>

@if (Route::has('password.request'))
<a href="{{ route('password.request') }}" class="text-blue-700 font-bold text-xs">
Forgot?
</a>
@endif
</div>

<div class="relative mt-2">
<span class="absolute left-3 top-3 text-gray-400 material-symbols-outlined">lock</span>

<input type="password" name="password" required
class="w-full pl-10 pr-10 py-3 bg-gray-100 rounded-lg focus:bg-white border-b-2 border-transparent focus:border-blue-900 outline-none">

<span class="absolute right-3 top-3 text-gray-400 material-symbols-outlined cursor-pointer">
visibility
</span>
</div>

<x-input-error :messages="$errors->get('password')" class="mt-1" />
</div>

<!-- REMEMBER -->
<div class="flex items-center gap-2">
<input type="checkbox" name="remember" class="rounded">
<span class="text-sm text-gray-600">Remember device for 30 days</span>
</div>

<!-- BUTTON -->
<button type="submit"
class="w-full bg-gradient-auth text-white py-3 rounded-lg font-bold flex justify-center items-center gap-2 hover:opacity-90">

Sign In
<span class="material-symbols-outlined">arrow_forward</span>

</button>

</form>

<!-- FOOTER -->
<div class="mt-8 pt-6 border-t text-center text-sm text-gray-500">
Don’t have an account?
<a href="{{ route('register') }}" class="text-blue-700 font-bold">
Register
</a>
</div>

</div>

<!-- ICONS -->
<div class="mt-10 flex justify-center gap-10 text-center">

<div>
<div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mx-auto">
<span class="material-symbols-outlined text-blue-900">security</span>
</div>
<p class="text-xs mt-2 text-gray-500 font-bold">SECURE</p>
</div>

<div>
<div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mx-auto">
<span class="material-symbols-outlined text-blue-900">speed</span>
</div>
<p class="text-xs mt-2 text-gray-500 font-bold">REAL-TIME</p>
</div>

<div>
<div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mx-auto">
<span class="material-symbols-outlined text-blue-900">verified_user</span>
</div>
<p class="text-xs mt-2 text-gray-500 font-bold">AUTHORIZED</p>
</div>

</div>

</div>

</main>

<!-- FOOTER -->
<footer class="text-center text-xs text-gray-400 pb-6">
© 2024 CrIServe Portal. Government of the Philippines.
</footer>

</body>
</html>