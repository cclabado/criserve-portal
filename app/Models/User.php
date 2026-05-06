<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'person_id',
    'service_provider_id',
    'position_id',
    'license_number',
    'approval_min_amount',
    'approval_max_amount',
    'email',
    'password',
    'role',
    'is_active',
    'deactivated_at',
    'first_name',
    'middle_name',
    'last_name',
    'extension_name',
    'birthdate',
    'sex',
    'civil_status',
    'google_email',
    'google_access_token',
    'google_refresh_token',
    'google_token_expires_at',
    'google_calendar_connected_at',
    'mfa_code_hash',
    'mfa_code_expires_at',
    'mfa_code_sent_at',
    'mfa_remember_token_hash',
    'mfa_remember_until',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'approval_min_amount' => 'decimal:2',
            'approval_max_amount' => 'decimal:2',
            'google_access_token' => 'encrypted',
            'google_refresh_token' => 'encrypted',
            'google_token_expires_at' => 'datetime',
            'google_calendar_connected_at' => 'datetime',
            'mfa_code_expires_at' => 'datetime',
            'mfa_code_sent_at' => 'datetime',
            'mfa_remember_until' => 'datetime',
        ];
    }

    public function handledApplications()
    {
        return $this->hasMany(Application::class, 'social_worker_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function requiresStaffPosition(): bool
    {
        return in_array($this->role, ['social_worker', 'approving_officer'], true);
    }

    public function hasGoogleCalendarConnection(): bool
    {
        return filled($this->google_refresh_token);
    }

    public function requiresMfa(): bool
    {
        return in_array($this->role, config('security.mfa.required_roles', []), true);
    }
}
