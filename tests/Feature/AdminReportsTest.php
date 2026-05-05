<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AssistanceSubtype;
use App\Models\AssistanceType;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_reports_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.reports'));

        $response->assertOk();
        $response->assertSee('Report Generation');
    }

    public function test_admin_can_filter_reports_and_download_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $socialWorker = User::factory()->create(['role' => 'social_worker', 'name' => 'SW One']);
        $approvingOfficer = User::factory()->create(['role' => 'approving_officer', 'name' => 'AO One']);
        $clientUser = User::factory()->create();
        $client = Client::create([
            'user_id' => $clientUser->id,
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juana',
            'sex' => 'Female',
        ]);
        $type = AssistanceType::create(['name' => 'Medical', 'is_active' => true]);
        $subtype = AssistanceSubtype::create([
            'assistance_type_id' => $type->id,
            'name' => 'Hospital Bill',
            'is_active' => true,
        ]);

        $application = Application::create([
            'user_id' => $clientUser->id,
            'client_id' => $client->id,
            'social_worker_id' => $socialWorker->id,
            'approving_officer_id' => $approvingOfficer->id,
            'reference_no' => 'APP-1001',
            'status' => 'approved',
            'assistance_type_id' => $type->id,
            'assistance_subtype_id' => $subtype->id,
            'client_sector' => 'Senior Citizen',
            'client_sub_category' => 'Medical Crisis',
            'gis_visit_type' => 'Walk-in',
            'amount_needed' => 12000,
            'recommended_amount' => 10000,
            'final_amount' => 9000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pageResponse = $this->actingAs($admin)->get(route('admin.reports', [
            'report_type' => 'daily',
            'status' => 'approved',
            'client_sector' => 'Senior Citizen',
        ]));

        $pageResponse->assertOk();
        $pageResponse->assertSee('APP-1001');

        $csvResponse = $this->actingAs($admin)->get(route('admin.reports', [
            'report_type' => 'daily',
            'status' => 'approved',
            'client_sector' => 'Senior Citizen',
            'format' => 'csv',
        ]));

        $csvResponse->assertOk();
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $csvResponse->streamedContent();
        $this->assertStringContainsString('APP-1001', $content);
        $this->assertStringContainsString('Senior Citizen', $content);
    }
}
