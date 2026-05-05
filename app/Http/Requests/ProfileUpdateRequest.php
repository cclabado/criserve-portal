<?php

namespace App\Http\Requests;

use App\Models\Position;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'extension_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'birthdate' => ['nullable', 'date'],
            'sex' => ['nullable', 'in:Male,Female'],
            'civil_status' => ['nullable', 'in:Single,Married,Widowed'],
            'position_id' => [
                Rule::requiredIf(fn () => in_array($this->user()->role, ['social_worker', 'approving_officer'], true)),
                'nullable',
                Rule::exists('positions', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'license_number' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if (! in_array($this->user()->role, ['social_worker', 'approving_officer'], true)) {
                    return;
                }

                $positionId = $this->input('position_id', $this->user()->position_id);
                $position = $positionId ? Position::query()->find((int) $positionId) : null;

                if ($position?->requires_license_number && blank($this->input('license_number'))) {
                    $validator->errors()->add('license_number', 'License number is required for the selected position.');
                }
            },
        ];
    }
}
