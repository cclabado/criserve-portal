<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_a_users_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'client']);

        $response = $this->actingAs($admin)->patch(route('admin.users.role.update', $user), [
            'role' => 'social_worker',
        ]);

        $response->assertRedirect(route('admin.users'));
        $this->assertSame('social_worker', $user->fresh()->role);
    }

    public function test_last_admin_can_not_remove_their_own_admin_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->from(route('admin.users'))->patch(route('admin.users.role.update', $admin), [
            'role' => 'client',
        ]);

        $response->assertRedirect(route('admin.users'));
        $response->assertSessionHasErrors('role');
        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_admin_can_update_user_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'client',
            'first_name' => 'Old',
            'last_name' => 'Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.users.update', $user), [
            'first_name' => 'Jane',
            'middle_name' => 'Q',
            'last_name' => 'Doe',
            'extension_name' => 'Jr',
            'email' => 'jane@example.com',
            'birthdate' => '1998-01-20',
            'sex' => 'Female',
            'civil_status' => 'Single',
            'role' => 'approving_officer',
        ]);

        $response->assertRedirect(route('admin.users'));

        $user->refresh();

        $this->assertSame('Jane', $user->first_name);
        $this->assertSame('Doe', $user->last_name);
        $this->assertSame('jane@example.com', $user->email);
        $this->assertSame('Jane Q Doe Jr', $user->name);
        $this->assertSame('approving_officer', $user->role);
    }
}
