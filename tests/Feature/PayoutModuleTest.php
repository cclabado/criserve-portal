<?php

namespace Tests\Feature;

use App\Models\PayoutBatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PayoutModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_payout_batch_from_uploaded_clean_list(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create(['role' => 'admin']);

        $file = UploadedFile::fake()->createWithContent('clean-list.csv', implode("\n", [
            'reference_no,last_name,first_name,birthdate,assistance_subtype,remarks',
            'REF-001,Dela Cruz,Juana,1990-01-01,Typhoon Aid,Verified in clean list',
            'REF-002,Santos,Pedro,1985-02-10,Typhoon Aid,Bring ID',
        ]));

        $response = $this->actingAs($admin)->post(route('admin.payouts.store'), [
            'batch_name' => 'Typhoon Relief Batch A',
            'sector_label' => 'Typhoon Affected',
            'venue' => 'City Gymnasium',
            'payout_amount' => '1500.00',
            'payout_date' => '2026-05-12',
            'spreadsheet' => $file,
        ]);

        $batch = PayoutBatch::first();

        $response->assertRedirect(route('admin.payouts.show', $batch));
        $this->assertNotNull($batch);
        $this->assertSame('Typhoon Relief Batch A', $batch->batch_name);
        $this->assertSame('1500.00', number_format((float) $batch->payout_amount, 2, '.', ''));
        $this->assertSame(2, $batch->entries()->count());
        $this->assertSame(2, $batch->summary['pending_count']);
    }

    public function test_reporting_officer_can_update_payout_status(): void
    {
        $officer = User::factory()->create(['role' => 'reporting_officer']);

        $batch = PayoutBatch::create([
            'user_id' => $officer->id,
            'access_role' => 'reporting_officer',
            'batch_name' => 'Drivers Batch 1',
            'sector_label' => 'Drivers',
            'venue' => 'Barangay Hall',
            'payout_amount' => 750,
            'payout_date' => '2026-05-12',
            'source_filename' => 'drivers-clean.xlsx',
            'upload_disk' => 'local',
            'upload_path' => 'payout-uploads/drivers-clean.xlsx',
            'summary' => [
                'total_entries' => 1,
                'pending_count' => 1,
                'paid_count' => 0,
                'absent_count' => 0,
                'deferred_count' => 0,
            ],
        ]);

        $entry = $batch->entries()->create([
            'sequence_no' => 1,
            'reference_no' => 'DRV-001',
            'full_name' => 'Mario Reyes',
            'payout_status' => 'pending',
        ]);

        $response = $this->actingAs($officer)->patch(route('reporting.payouts.entries.update', [$batch, $entry]), [
            'payout_status' => 'paid',
            'payout_notes' => 'Released after ID validation.',
        ]);

        $response->assertSessionHasErrors('proof_photo_data');

        Storage::fake('local');

        $response = $this->actingAs($officer)->patch(route('reporting.payouts.entries.update', [$batch, $entry]), [
            'payout_status' => 'paid',
            'payout_notes' => 'Released after ID validation.',
            'proof_photo' => UploadedFile::fake()->image('proof.jpg'),
        ]);

        $response->assertRedirect(route('reporting.payouts.show', $batch));
        $entry->refresh();
        $batch->refresh();

        $this->assertSame('paid', $entry->payout_status);
        $this->assertNotNull($entry->paid_at);
        $this->assertNotNull($entry->proof_photo_path);
        $this->assertSame(1, $batch->summary['paid_count']);
        $this->assertSame(0, $batch->summary['pending_count']);
    }

    public function test_admin_can_activate_batch_for_internal_staff_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $socialWorker = User::factory()->create(['role' => 'social_worker']);

        $batch = PayoutBatch::create([
            'user_id' => $admin->id,
            'access_role' => 'admin',
            'batch_name' => 'Fire Victims Batch 1',
            'sector_label' => 'Fire Victims',
            'venue' => 'Barangay Hall',
            'payout_amount' => 1000,
            'payout_date' => '2026-05-12',
            'source_filename' => 'fire-victims-clean.xlsx',
            'upload_disk' => 'local',
            'upload_path' => 'payout-uploads/fire-victims-clean.xlsx',
            'summary' => [
                'total_entries' => 0,
                'pending_count' => 0,
                'paid_count' => 0,
                'absent_count' => 0,
                'deferred_count' => 0,
            ],
            'is_active' => false,
        ]);

        $this->actingAs($socialWorker)
            ->get(route('socialworker.payouts.index'))
            ->assertDontSee('Fire Victims Batch 1');

        $this->actingAs($socialWorker)
            ->get(route('socialworker.payouts.show', $batch))
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.payouts.activation.update', $batch), [
                'is_active' => 1,
            ])
            ->assertRedirect();

        $batch->refresh();

        $this->assertTrue($batch->is_active);
        $this->assertSame($admin->id, $batch->activated_by_user_id);
        $this->assertNotNull($batch->activated_at);

        $this->actingAs($socialWorker)
            ->get(route('socialworker.payouts.index'))
            ->assertSee('Fire Victims Batch 1');

        $this->actingAs($socialWorker)
            ->get(route('socialworker.payouts.show', $batch))
            ->assertOk();
    }

    public function test_technical_and_admin_staff_can_access_only_activated_payouts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technicalStaff = User::factory()->create(['role' => 'technical_staff']);
        $adminStaff = User::factory()->create(['role' => 'admin_staff']);

        $batch = PayoutBatch::create([
            'user_id' => $admin->id,
            'access_role' => 'admin',
            'batch_name' => 'Drivers Batch Shared',
            'sector_label' => 'Drivers',
            'venue' => 'Covered Court',
            'payout_amount' => 900,
            'payout_date' => '2026-05-12',
            'source_filename' => 'drivers-clean.xlsx',
            'upload_disk' => 'local',
            'upload_path' => 'payout-uploads/drivers-clean.xlsx',
            'summary' => [
                'total_entries' => 0,
                'pending_count' => 0,
                'paid_count' => 0,
                'absent_count' => 0,
                'deferred_count' => 0,
            ],
            'is_active' => false,
        ]);

        $this->actingAs($technicalStaff)
            ->get(route('technical-staff.payouts.show', $batch))
            ->assertForbidden();

        $this->actingAs($adminStaff)
            ->get(route('admin-staff.payouts.show', $batch))
            ->assertForbidden();

        $batch->update([
            'is_active' => true,
            'activated_by_user_id' => $admin->id,
            'activated_at' => now(),
        ]);

        $this->actingAs($technicalStaff)
            ->get(route('technical-staff.payouts.index'))
            ->assertSee('Drivers Batch Shared');

        $this->actingAs($technicalStaff)
            ->get(route('technical-staff.payouts.show', $batch))
            ->assertOk();

        $this->actingAs($adminStaff)
            ->get(route('admin-staff.payouts.index'))
            ->assertSee('Drivers Batch Shared');

        $this->actingAs($adminStaff)
            ->get(route('admin-staff.payouts.show', $batch))
            ->assertOk();
    }

    public function test_record_locked_by_one_user_cannot_be_updated_by_another_user(): void
    {
        Storage::fake('local');

        $userOne = User::factory()->create(['role' => 'technical_staff']);
        $userTwo = User::factory()->create(['role' => 'admin_staff']);

        $batch = PayoutBatch::create([
            'user_id' => User::factory()->create(['role' => 'admin'])->id,
            'access_role' => 'admin',
            'batch_name' => 'Shared Payout Lock Test',
            'sector_label' => 'Drivers',
            'venue' => 'Barangay Hall',
            'payout_amount' => 1000,
            'payout_date' => '2026-05-12',
            'source_filename' => 'shared-clean.xlsx',
            'upload_disk' => 'local',
            'upload_path' => 'payout-uploads/shared-clean.xlsx',
            'summary' => [
                'total_entries' => 1,
                'pending_count' => 1,
                'paid_count' => 0,
                'absent_count' => 0,
                'deferred_count' => 0,
            ],
            'is_active' => true,
        ]);

        $entry = $batch->entries()->create([
            'sequence_no' => 1,
            'reference_no' => 'LOCK-001',
            'full_name' => 'Juana Dela Cruz',
            'payout_status' => 'pending',
            'handling_by_user_id' => $userOne->id,
            'handling_started_at' => Carbon::now(),
        ]);

        $this->actingAs($userTwo)
            ->post(route('admin-staff.payouts.entries.claim', [$batch, $entry]))
            ->assertStatus(423);

        $this->actingAs($userTwo)
            ->patch(route('admin-staff.payouts.entries.update', [$batch, $entry]), [
                'payout_status' => 'paid',
                'proof_photo' => UploadedFile::fake()->image('proof.jpg'),
            ])
            ->assertSessionHasErrors('payout_status');

        $this->actingAs($userOne)
            ->post(route('technical-staff.payouts.entries.release', [$batch, $entry]))
            ->assertOk();

        $this->actingAs($userTwo)
            ->post(route('admin-staff.payouts.entries.claim', [$batch, $entry]))
            ->assertOk();
    }

    public function test_admin_and_reporting_officer_can_generate_payout_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $officer = User::factory()->create(['role' => 'reporting_officer']);

        $batch = PayoutBatch::create([
            'user_id' => $officer->id,
            'access_role' => 'reporting_officer',
            'batch_name' => 'Typhoon Payout Report',
            'sector_label' => 'Typhoon Affected',
            'venue' => 'City Gymnasium',
            'payout_amount' => 1200,
            'payout_date' => '2026-05-13',
            'source_filename' => 'typhoon-clean.xlsx',
            'upload_disk' => 'local',
            'upload_path' => 'payout-uploads/typhoon-clean.xlsx',
            'summary' => [
                'total_entries' => 1,
                'pending_count' => 1,
                'paid_count' => 0,
                'absent_count' => 0,
                'deferred_count' => 0,
            ],
            'is_active' => true,
        ]);

        $batch->entries()->create([
            'sequence_no' => 1,
            'reference_no' => 'TYP-001',
            'full_name' => 'Pedro Santos',
            'payout_status' => 'pending',
        ]);

        $adminResponse = $this->actingAs($admin)->get(route('admin.payouts.report', $batch));
        $adminResponse->assertOk();
        $adminResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $officerResponse = $this->actingAs($officer)->get(route('reporting.payouts.report', $batch));
        $officerResponse->assertOk();
        $officerResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
