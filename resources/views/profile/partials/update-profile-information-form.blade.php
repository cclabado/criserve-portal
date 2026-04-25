<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="first_name" :value="__('First Name')" />
                <x-text-input id="first_name" name="first_name" type="text" class="mt-1 block w-full" :value="old('first_name', $user->first_name)" autofocus autocomplete="given-name" />
                <x-input-error class="mt-2" :messages="$errors->get('first_name')" />
            </div>

            <div>
                <x-input-label for="middle_name" :value="__('Middle Name')" />
                <x-text-input id="middle_name" name="middle_name" type="text" class="mt-1 block w-full" :value="old('middle_name', $user->middle_name)" />
                <x-input-error class="mt-2" :messages="$errors->get('middle_name')" />
            </div>

            <div>
                <x-input-label for="last_name" :value="__('Last Name')" />
                <x-text-input id="last_name" name="last_name" type="text" class="mt-1 block w-full" :value="old('last_name', $user->last_name)" autocomplete="family-name" />
                <x-input-error class="mt-2" :messages="$errors->get('last_name')" />
            </div>

            <div>
                <x-input-label for="extension_name" :value="__('Extension')" />
                <x-text-input id="extension_name" name="extension_name" type="text" class="mt-1 block w-full" :value="old('extension_name', $user->extension_name)" />
                <x-input-error class="mt-2" :messages="$errors->get('extension_name')" />
            </div>
        </div>

        <div>
            <x-input-label for="name" :value="__('Display Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <div>
                <x-input-label for="birthdate" :value="__('Birthdate')" />
                <x-text-input id="birthdate" name="birthdate" type="date" class="mt-1 block w-full" :value="old('birthdate', $user->birthdate)" />
                <x-input-error class="mt-2" :messages="$errors->get('birthdate')" />
            </div>

            <div>
                <x-input-label for="sex" :value="__('Sex')" />
                <select id="sex" name="sex" class="input mt-1 block w-full">
                    <option value="">Select</option>
                    <option value="Male" @selected(old('sex', $user->sex) === 'Male')>Male</option>
                    <option value="Female" @selected(old('sex', $user->sex) === 'Female')>Female</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('sex')" />
            </div>

            <div>
                <x-input-label for="civil_status" :value="__('Civil Status')" />
                <select id="civil_status" name="civil_status" class="input mt-1 block w-full">
                    <option value="">Select</option>
                    <option value="Single" @selected(old('civil_status', $user->civil_status) === 'Single')>Single</option>
                    <option value="Married" @selected(old('civil_status', $user->civil_status) === 'Married')>Married</option>
                    <option value="Widowed" @selected(old('civil_status', $user->civil_status) === 'Widowed')>Widowed</option>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('civil_status')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
