<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Position;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogs
    ) {
    }

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
        $user = $request->user();

        if (! empty($validated['first_name']) || ! empty($validated['last_name'])) {
            $validated['name'] = trim(implode(' ', array_filter([
                $validated['first_name'] ?? null,
                $validated['middle_name'] ?? null,
                $validated['last_name'] ?? null,
                $validated['extension_name'] ?? null,
            ])));
        }

        if (! $user->requiresStaffPosition()) {
            unset($validated['position_id'], $validated['license_number']);
        } else {
            $position = ! empty($validated['position_id'])
                ? Position::query()->find((int) $validated['position_id'])
                : null;

            $validated['license_number'] = $position?->requires_license_number
                ? (filled($validated['license_number'] ?? null) ? trim((string) $validated['license_number']) : null)
                : null;
        }

        unset($validated['signature_file']);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('signature_file') && in_array($user->role, ['social_worker', 'approving_officer'], true)) {
            $disk = config('filesystems.default', 'local');
            $directory = 'signatures/staff';
            $file = $request->file('signature_file');

            if ($user->signature_path && $user->signature_disk) {
                Storage::disk($user->signature_disk)->delete($user->signature_path);
            }

            $path = $file->storeAs(
                $directory,
                Str::uuid().'.png',
                $disk
            );

            $user->signature_path = $path;
            $user->signature_disk = $disk;
            $user->signature_mime_type = $file->getMimeType() ?: 'image/png';
        }

        $user->save();
        $this->auditLogs->log($request, 'profile.updated', $user);

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
        $this->auditLogs->log($request, 'profile.deactivated', $user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
