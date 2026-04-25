<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Client;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_view_their_own_document_but_not_other_clients_document(): void
    {
        Storage::fake('public');

        [$owner, $ownerDocument] = $this->createClientDocument('owner@example.com');
        [$otherClient] = $this->createClientDocument('other@example.com');

        Storage::disk('public')->put($ownerDocument->file_path, 'fake-pdf-content');

        $this->actingAs($owner)
            ->get(route('documents.show', $ownerDocument))
            ->assertOk();

        $this->actingAs($otherClient)
            ->get(route('documents.show', $ownerDocument))
            ->assertForbidden();
    }

    public function test_social_worker_can_view_any_client_document(): void
    {
        Storage::fake('public');

        [, $document] = $this->createClientDocument('client@example.com');
        $socialWorker = User::factory()->create(['role' => 'social_worker']);

        Storage::disk('public')->put($document->file_path, 'fake-pdf-content');

        $this->actingAs($socialWorker)
            ->get(route('documents.show', $document))
            ->assertOk();
    }

    protected function createClientDocument(string $email): array
    {
        $user = User::factory()->create([
            'role' => 'client',
            'email' => $email,
        ]);

        $client = Client::create([
            'user_id' => $user->id,
            'last_name' => 'Client',
            'first_name' => 'Sample',
        ]);

        $application = Application::create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'reference_no' => 'APP-2026-04-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'status' => 'submitted',
        ]);

        $document = Document::create([
            'application_id' => $application->id,
            'file_name' => 'sample.pdf',
            'file_path' => 'documents/sample-'.$user->id.'.pdf',
        ]);

        return [$user, $document];
    }
}
