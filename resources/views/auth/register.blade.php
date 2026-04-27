<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CrIServe Portal Registration</title>

<script src="https://cdn.tailwindcss.com"></script>

<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<style>
body { font-family: 'Inter', sans-serif; }

.input {
    width: 100%;
    padding: 12px;
    background: #f1f5f9;
    border-radius: 8px;
    border: 2px solid transparent;
}
.input:focus {
    outline: none;
    border-color: #0B3C5D;
    background: white;
}
</style>

</head>

<body class="bg-gray-100">

<header class="flex justify-between items-center px-8 py-4 bg-white shadow">
    <h1 class="font-bold text-[#0B3C5D]">CrIServe Portal</h1>

    <div class="flex items-center gap-4">
        <span class="text-gray-600">Already have an account?</span>
        <a href="{{ route('login') }}" class="bg-[#0B3C5D] text-white px-4 py-2 rounded-lg font-semibold hover:opacity-90">
            Log In
        </a>
    </div>
</header>

<div class="max-w-6xl mx-auto mt-10 grid grid-cols-12 gap-8 px-6">

<div class="col-span-4 bg-gradient-to-br from-[#0B3C5D] to-[#174A6B] text-white p-8 rounded-xl">
<h2 class="text-2xl font-bold mb-4">CrIServe Portal Registration</h2>
<p class="text-sm mb-6 text-blue-100">
Join the unified digital infrastructure for public service.
</p>
<ul class="space-y-3 text-sm">
<li>- End-to-end encrypted identity verification</li>
<li>- Personalized service dashboard</li>
</ul>
</div>

<div class="col-span-8 bg-white rounded-xl p-8 shadow">

<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
<p class="font-bold">One client, one account only.</p>
<p class="mt-1">
If you already registered before, please sign in with your existing account. If you forgot your password, use password reset through your email. If you no longer have access to that email, please contact support for account recovery.
</p>
<div class="mt-4 flex flex-wrap gap-3">
<a href="{{ route('login') }}" class="inline-flex items-center rounded-lg bg-[#0B3C5D] px-4 py-2 text-xs font-bold text-white hover:opacity-90">
Sign In
</a>
<a href="{{ route('password.request') }}" class="inline-flex items-center rounded-lg border border-amber-300 bg-white px-4 py-2 text-xs font-bold text-amber-900 hover:bg-amber-100">
Forgot Password
</a>
<a href="{{ route('support.create', ['source' => 'account-recovery']) }}" class="inline-flex items-center rounded-lg border border-amber-300 bg-white px-4 py-2 text-xs font-bold text-amber-900 hover:bg-amber-100">
Contact Support
</a>
</div>
</div>

@if ($errors->any())
<div class="mb-4 rounded-xl border border-red-300 bg-red-100 p-4 text-red-700">
<ul class="text-sm space-y-1">
@foreach ($errors->all() as $error)
<li>- {{ $error }}</li>
@endforeach
</ul>
<div class="mt-4 flex flex-wrap gap-3">
<a href="{{ route('login') }}" class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-xs font-bold text-white hover:bg-red-700">
Go to Sign In
</a>
<a href="{{ route('password.request') }}" class="inline-flex items-center rounded-lg border border-red-300 bg-white px-4 py-2 text-xs font-bold text-red-700 hover:bg-red-50">
Reset Password
</a>
<a href="{{ route('support.create', ['source' => 'account-recovery']) }}" class="inline-flex items-center rounded-lg border border-red-300 bg-white px-4 py-2 text-xs font-bold text-red-700 hover:bg-red-50">
Contact Support
</a>
</div>
</div>
@endif

@if(session('success'))
<div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-lg">
{{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('register') }}" class="space-y-8">
@csrf

<div>
<span class="text-xs font-bold bg-gray-200 px-2 py-1 rounded">STEP 1</span>
<h3 class="text-lg font-bold mt-2 text-[#0B3C5D]">Account Credentials</h3>

<div class="mt-4 space-y-4">

<div>
<label class="text-sm font-semibold text-gray-600">Email Address</label>
<input type="email" name="email" value="{{ old('email') }}" class="input" required>
</div>

<div class="grid grid-cols-2 gap-4">

<div>
<label class="text-sm font-semibold text-gray-600">Password</label>
<input type="password" name="password" class="input" required>
</div>

<div>
<label class="text-sm font-semibold text-gray-600">Confirm Password</label>
<input type="password" name="password_confirmation" class="input" required>
</div>

</div>

</div>
</div>

<div>
<span class="text-xs font-bold bg-gray-200 px-2 py-1 rounded">STEP 2</span>
<h3 class="text-lg font-bold mt-2 text-[#0B3C5D]">Personal Information</h3>

<div class="mt-4 space-y-4">

<div class="grid grid-cols-3 gap-4">

<div>
<label class="text-sm font-semibold text-gray-600">First Name</label>
<input name="first_name" value="{{ old('first_name') }}" class="input" required>
</div>

<div>
<label class="text-sm font-semibold text-gray-600">Middle Name</label>
<input name="middle_name" value="{{ old('middle_name') }}" class="input">
</div>

<div>
<label class="text-sm font-semibold text-gray-600">Last Name</label>
<input name="last_name" value="{{ old('last_name') }}" class="input" required>
</div>

</div>

<div class="grid grid-cols-3 gap-4">

<div>
<label class="text-sm font-semibold text-gray-600">Extension</label>
<input type="text" name="extension_name" value="{{ old('extension_name') }}" class="input">
</div>

<div>
<label class="text-sm font-semibold text-gray-600">Birthdate</label>
<input type="date" name="birthdate" value="{{ old('birthdate') }}" class="input" required>
</div>

<div>
<label class="text-sm font-semibold text-gray-600">Sex</label>
<select name="sex" class="input" required>
<option value="">Select Sex</option>
<option value="Male" {{ old('sex')=='Male'?'selected':'' }}>Male</option>
<option value="Female" {{ old('sex')=='Female'?'selected':'' }}>Female</option>
</select>
</div>

</div>

<div>
<label class="text-sm font-semibold text-gray-600">Civil Status</label>
<select name="civil_status" class="input" required>
<option value="">Select Status</option>
<option {{ old('civil_status')=='Single'?'selected':'' }}>Single</option>
<option {{ old('civil_status')=='Married'?'selected':'' }}>Married</option>
<option {{ old('civil_status')=='Widowed'?'selected':'' }}>Widowed</option>
<option {{ old('civil_status')=='Separated'?'selected':'' }}>Separated</option>
</select>
</div>

</div>
</div>

<div class="flex justify-end gap-4 pt-6 border-t">

<button type="reset" class="px-6 py-3 bg-gray-200 rounded-lg">
Reset
</button>

<button type="submit"
class="px-8 py-3 bg-gradient-to-r from-[#0B3C5D] to-[#174A6B] text-white rounded-lg font-semibold hover:opacity-90 shadow">
Create Account
</button>

</div>

</form>

</div>

</div>

</body>
</html>
