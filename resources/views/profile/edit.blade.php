@extends('layouts.app')

@section('content')

<main class="max-w-5xl mx-auto space-y-6">

    <section class="profile-hero">
        <div>
            <p class="profile-kicker">Account</p>
            <h1 class="profile-title">Profile Settings</h1>
            <p class="profile-copy">
                Update your personal details, email address, password, and account settings.
            </p>
        </div>
    </section>

    <section class="profile-card">
        <div class="max-w-3xl">
            @include('profile.partials.update-profile-information-form', ['positions' => $positions])
        </div>
    </section>

    @if($user->canAccessSocialWorkerModule())
        <section class="profile-card">
            <div class="max-w-3xl">
                @include('profile.partials.google-calendar-connection')
            </div>
        </section>
    @endif

    <section class="profile-card">
        <div class="max-w-3xl">
            @include('profile.partials.update-password-form')
        </div>
    </section>

    <section class="profile-card">
        <div class="max-w-3xl">
            @include('profile.partials.delete-user-form')
        </div>
    </section>

</main>

<style>
.profile-hero{
    padding:28px 30px;
    border-radius:24px;
    background:
        radial-gradient(circle at top left, rgba(184, 220, 244, .55), transparent 32%),
        linear-gradient(135deg, #ffffff 0%, #edf5fb 100%);
    border:1px solid #d9e6f0;
}
.profile-kicker{
    font-size:11px;
    font-weight:800;
    letter-spacing:.18em;
    text-transform:uppercase;
    color:#567189;
}
.profile-title{
    margin-top:10px;
    font-size:34px;
    font-weight:900;
    color:#163750;
}
.profile-copy{
    margin-top:10px;
    color:#64748b;
    max-width:760px;
}
.profile-card{
    background:#fff;
    border:1px solid #e2e8f0;
    border-radius:22px;
    padding:24px;
    box-shadow:0 14px 28px rgba(15, 23, 42, .04);
}
</style>

@endsection
