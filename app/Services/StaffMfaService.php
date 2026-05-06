<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\StaffMfaCodeNotification;
use Illuminate\Contracts\Cookie\QueueingFactory as CookieQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StaffMfaService
{
    public function __construct(
        protected CookieQueue $cookies
    ) {
    }

    public function requiresMfa(User $user): bool
    {
        return $user->requiresMfa();
    }

    public function issueChallenge(User $user): string
    {
        $length = max(6, (int) config('security.mfa.code_length', 6));
        $max = (10 ** $length) - 1;
        $code = str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);

        $user->forceFill([
            'mfa_code_hash' => Hash::make($code),
            'mfa_code_expires_at' => now()->addMinutes((int) config('security.mfa.expires_minutes', 10)),
            'mfa_code_sent_at' => now(),
        ])->save();

        $user->notify(new StaffMfaCodeNotification($code, (int) config('security.mfa.expires_minutes', 10)));

        return $code;
    }

    public function verifyChallenge(User $user, string $code): void
    {
        if (! $user->mfa_code_hash || ! $user->mfa_code_expires_at || now()->greaterThan($user->mfa_code_expires_at)) {
            throw ValidationException::withMessages([
                'code' => 'The verification code has expired. Please request a new one.',
            ]);
        }

        if (! Hash::check($code, $user->mfa_code_hash)) {
            throw ValidationException::withMessages([
                'code' => 'The verification code is incorrect.',
            ]);
        }
    }

    public function clearChallenge(User $user): void
    {
        $user->forceFill([
            'mfa_code_hash' => null,
            'mfa_code_expires_at' => null,
            'mfa_code_sent_at' => null,
        ])->save();
    }

    public function hasRememberedDevice(Request $request, User $user): bool
    {
        $cookie = (string) $request->cookie((string) config('security.mfa.cookie_name', 'criserve_mfa_remember'));

        if ($cookie === '' || ! $user->mfa_remember_token_hash || ! $user->mfa_remember_until) {
            return false;
        }

        if (now()->greaterThan($user->mfa_remember_until)) {
            $this->clearRememberedDevice($user);

            return false;
        }

        [$userId, $token] = array_pad(explode('|', $cookie, 2), 2, null);

        return (int) $userId === (int) $user->id
            && filled($token)
            && Hash::check($token, $user->mfa_remember_token_hash);
    }

    public function rememberDevice(User $user): void
    {
        $token = Str::random(64);
        $days = max(1, (int) config('security.mfa.remember_days', 30));

        $user->forceFill([
            'mfa_remember_token_hash' => Hash::make($token),
            'mfa_remember_until' => now()->addDays($days),
        ])->save();

        $this->cookies->queue(
            cookie(
                (string) config('security.mfa.cookie_name', 'criserve_mfa_remember'),
                $user->id.'|'.$token,
                $days * 24 * 60,
                null,
                null,
                request()->isSecure(),
                true,
                false,
                'lax'
            )
        );
    }

    public function forgetRememberedDevice(): void
    {
        $this->cookies->queue(
            $this->cookies->forget((string) config('security.mfa.cookie_name', 'criserve_mfa_remember'))
        );
    }

    public function clearRememberedDevice(User $user): void
    {
        $user->forceFill([
            'mfa_remember_token_hash' => null,
            'mfa_remember_until' => null,
        ])->save();
    }
}
