<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AssistanceDetail;
use App\Models\AssistanceSubtype;
use App\Models\AssistanceType;
use App\Models\Client;
use App\Models\Document;
use App\Models\ModeOfAssistance;
use App\Models\ServicePoint;
use App\Models\User;
use App\Notifications\ClientDocumentComplianceRequestedNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClientDocumentComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_social_worker_can_request_document_compliance_and_notify_client(): void
    {
        Notification::fake();
        $this->seed(DatabaseSeeder::class);

        $socialWorker = User::factory()->create(['role' => 'social_worker']);
        $clientUser = User::factory()->create(['role' => 'client', 'first_name' => 'Juan']);

        $client = Client::create([
            'user_id' => $clientUser->id,
            'last_name' => 'Dela Cruz',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'contact_number' => '09123456789',
            'full_address' => 'Sample Address',
            'sex' => 'Male',
            'birthdate' => '1990-05-01',
            'civil_status' => 'Single',
        ]);

        $type = AssistanceType::where('name', 'Financial Assistance')->firstOrFail();
        $subtype = AssistanceSubtype::where('name', 'Medical Assistance')->firstOrFail();
        $detail = AssistanceDetail::where('name', 'Payment for Hospital Bill')->firstOrFail();
        $mode = ModeOfAssistance::where('name', 'Cash')->firstOrFail();
        ServicePoint::create(['name' => 'Walk-in', 'is_active' => true]);

        $application = Application::create([
            'client_id' => $client->id,
            'user_id' => $clientUser->id,
            'reference_no' => 'REF-COMP-001',
            'status' => 'submitted',
        ]);

        $document = Document::create([
            'application_id' => $application->id,
            'document_type' => 'Valid ID',
            'file_name' => 'valid-id.pdf',
            'file_path' => 'documents/valid-id.pdf',
            'storage_disk' => 'local',
            'mime_type' => 'application/pdf',
            'file_size' => 1234,
            'file_hash' => 'hash',
        ]);

        $response = $this
            ->actingAs($socialWorker)
            ->post(route('socialworker.assess.update', $application->id), [
                'client_last_name' => 'Dela Cruz',
                'client_first_name' => 'Juan',
                'client_middle_name' => 'Santos',
                'client_extension_name' => null,
                'client_contact_number' => '09123456789',
                'client_address' => 'Sample Address',
                'client_sex' => 'Male',
                'client_birthdate' => '1990-05-01',
                'client_civil_status' => 'Single',
                'gis_visit_type' => 'Walk-in',
                'assistance_type_id' => $type->id,
                'assistance_subtype_id' => $subtype->id,
                'assistance_detail_id' => $detail->id,
                'mode_of_assistance_id' => $mode->id,
                'notes' => 'Client uploaded the wrong file.',
                'assessment_action' => 'request_document_compliance',
                'client_compliance_notes' => 'Please upload a clear and readable government-issued ID.',
                'compliance_document_ids' => [$document->id],
                'remarks' => [
                    $document->id => 'Wrong attachment uploaded. Please replace with a readable government-issued ID.',
                ],
            ]);

        $response->assertRedirect(route('socialworker.applications'));
        $response->assertSessionHas('success', 'Client has been notified to comply with the flagged document requirements.');

        $application->refresh();
        $document->refresh();

        $this->assertSame('requested', $application->client_compliance_status);
        $this->assertSame('Please upload a clear and readable government-issued ID.', $application->client_compliance_notes);
        $this->assertSame('under_review', $application->status);
        $this->assertTrue($document->requires_client_resubmission);
        $this->assertStringContainsString('Wrong attachment uploaded', (string) $document->remarks);

        Notification::assertSentTo($clientUser, ClientDocumentComplianceRequestedNotification::class);
    }

    public function test_client_can_upload_corrected_documents_for_compliance(): void
    {
        $clientUser = User::factory()->create(['role' => 'client']);

        $client = Client::create([
            'user_id' => $clientUser->id,
            'last_name' => 'Dela Cruz',
            'first_name' => 'Maria',
            'contact_number' => '09123456789',
            'full_address' => 'Sample Address',
            'sex' => 'Female',
            'birthdate' => '1994-05-01',
            'civil_status' => 'Single',
        ]);

        $application = Application::create([
            'client_id' => $client->id,
            'user_id' => $clientUser->id,
            'reference_no' => 'REF-COMP-002',
            'status' => 'under_review',
            'client_compliance_status' => 'requested',
            'client_compliance_notes' => 'Please upload a readable hospital bill.',
            'client_compliance_requested_at' => now(),
        ]);

        $response = $this
            ->actingAs($clientUser)
            ->post(route('client.application.compliance-documents.upload', $application->id), [
                'documents' => [
                    UploadedFile::fake()->create('hospital-bill.pdf', 200, 'application/pdf'),
                ],
                'compliance_note' => 'Uploaded a clearer scanned copy.',
            ]);

        $response->assertRedirect(route('client.application.show', $application->id));
        $response->assertSessionHas('success');

        $application->refresh();
        $this->assertSame('resubmitted', $application->client_compliance_status);
        $this->assertNotNull($application->client_compliance_responded_at);

        $uploadedDocument = $application->documents()->latest('id')->first();

        $this->assertNotNull($uploadedDocument);
        $this->assertSame('Compliance Resubmission', $uploadedDocument->document_type);
        $this->assertStringContainsString('Uploaded a clearer scanned copy.', (string) $uploadedDocument->remarks);
    }
}
