<section
    x-data="{
        role: @js($user->role),
        selectedPositionId: @js((string) old('position_id', $user->position_id ?? '')),
        positions: @js(($positions ?? collect())->mapWithKeys(fn ($position) => [
            (string) $position->id => [
                'requires_license_number' => (bool) $position->requires_license_number,
            ],
        ])),
        get isStaffRole() {
            return ['social_worker', 'approving_officer'].includes(this.role);
        },
        get requiresLicense() {
            return Boolean(this.positions?.[this.selectedPositionId]?.requires_license_number);
        },
    }"
>
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

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-6">
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

        <div x-show="isStaffRole" x-cloak class="grid gap-4 md:grid-cols-2">
            <div>
                <x-input-label for="position_id" :value="__('Position')" />
                <select id="position_id"
                        name="position_id"
                        class="input mt-1 block w-full"
                        x-model="selectedPositionId">
                    <option value="">Select position</option>
                    @foreach(($positions ?? collect()) as $position)
                        <option value="{{ $position->id }}" @selected((string) old('position_id', $user->position_id ?? '') === (string) $position->id)>
                            {{ $position->name }}
                            @if($position->salary_grade)
                                (SG {{ $position->salary_grade }})
                            @endif
                        </option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('position_id')" />
            </div>

            <div x-show="requiresLicense" x-cloak>
                <x-input-label for="license_number" :value="__('License Number')" />
                <x-text-input id="license_number"
                              name="license_number"
                              type="text"
                              class="mt-1 block w-full"
                              :value="old('license_number', $user->license_number)" />
                <x-input-error class="mt-2" :messages="$errors->get('license_number')" />
            </div>
        </div>

        <div x-show="isStaffRole" x-cloak class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-5">
            <div>
                <h3 class="text-base font-semibold text-slate-900">E-Signature</h3>
                <p class="mt-1 text-sm text-slate-500">Upload a PNG signature that will appear on generated staff documents.</p>
            </div>

            @if($user->signatureDataUrl())
                <div>
                    <p class="text-sm font-medium text-slate-700">Current signature</p>
                    <div class="mt-3 rounded-xl border border-slate-200 bg-white p-4">
                        <img src="{{ $user->signatureDataUrl() }}" alt="Current e-signature" class="max-h-24 w-auto object-contain">
                    </div>
                </div>
            @endif

            <div>
                <x-input-label for="signature_file" :value="__('Upload PNG Signature')" />
                <input id="signature_file" name="signature_file" type="file" accept="image/png" class="input mt-1 block w-full bg-white">
                <p class="mt-2 text-xs text-slate-500">PNG only. Recommended transparent background for clean print output.</p>
                <x-input-error class="mt-2" :messages="$errors->get('signature_file')" />
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
