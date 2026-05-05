<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Position;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'positions' => Position::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        if (! empty($validated['first_name']) || ! empty($validated['last_name'])) {
            $validated['name'] = trim(implode(' ', array_filter([
                $validated['first_name'] ?? null,
                $validated['middle_name'] ?? null,
                $validated['last_name'] ?? null,
                $validated['extension_name'] ?? null,
            ])));
        }

        if (! $request->user()->requiresStaffPosition()) {
            unset($validated['position_id'], $validated['license_number']);
        } else {
            $position = ! empty($validated['position_id'])
                ? Position::query()->find((int) $validated['position_id'])
                : null;

            $validated['license_number'] = $position?->requires_license_number
                ? (filled($validated['license_number'] ?? null) ? trim((string) $validated['license_number']) : null)
                : null;
        }

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->forceFill([
            'is_active' => false,
            'deactivated_at' => now(),
        ])->save();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
