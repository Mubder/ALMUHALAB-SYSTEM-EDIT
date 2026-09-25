<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClientIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $clientA;
    private User $clientB;
    private User $admin;
    private ServiceRequest $requestA;
    private ServiceRequest $requestB;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        // Client role: only the baseline permissions every client has
        $clientRole = Role::create(['name' => 'User']);
        $clientRole->permissions()->sync(
            Permission::whereIn('name', ['create_request', 'view_request'])->pluck('id')
        );

        $adminRole = Role::create(['name' => 'Admin']);
        $adminRole->permissions()->sync(Permission::all()->pluck('id'));

        $this->clientA = User::factory()->create();
        $this->clientA->role()->associate($clientRole)->save();

        $this->clientB = User::factory()->create();
        $this->clientB->role()->associate($clientRole)->save();

        $this->admin = User::factory()->create();
        $this->admin->role()->associate($adminRole)->save();

        $this->requestA = $this->makeRequest($this->clientA, 'Passport renewal for client A');
        $this->requestB = $this->makeRequest($this->clientB, 'Visa application for client B');
    }

    private function makeRequest(User $owner, string $title): ServiceRequest
    {
        return ServiceRequest::create([
            'user_id'       => $owner->id,
            'title'         => $title,
            'description'   => 'Confidential request data of ' . $owner->name,
            'status'        => 'New',
            'current_stage' => 1,
        ]);
    }

    public function test_client_cannot_view_another_clients_request(): void
    {
        $this->actingAs($this->clientB)
            ->get("/service-requests/{$this->requestA->id}")
            ->assertForbidden();
    }

    public function test_client_cannot_edit_another_clients_request(): void
    {
        $this->actingAs($this->clientB)
            ->get("/service-requests/{$this->requestA->id}/edit")
            ->assertForbidden();

        $this->actingAs($this->clientB)
            ->put("/service-requests/{$this->requestA->id}", [
                'title'       => 'Hijacked title',
                'description' => 'Hijacked description',
            ])
            ->assertForbidden();
    }

    public function test_client_cannot_delete_another_clients_request_files(): void
    {
        $attachment = $this->requestA->attachments()->create([
            'file_path'     => 'service_requests/confidential.pdf',
            'original_name' => 'confidential.pdf',
            'file_size'     => 12,
            'visibility'    => 'all',
        ]);
        Storage::disk('public')->put('service_requests/confidential.pdf', 'secret');

        $this->actingAs($this->clientB)
            ->delete("/service-requests/{$this->requestA->id}/files/{$attachment->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
    }

    public function test_client_index_only_lists_own_requests(): void
    {
        $response = $this->actingAs($this->clientB)->get('/service-requests');
        $response->assertOk();

        $response->assertSee($this->requestB->title);
        $response->assertDontSee($this->requestA->title);
        $response->assertDontSee($this->clientA->name);
    }

    public function test_client_cannot_download_another_clients_attachment(): void
    {
        $attachment = $this->requestA->attachments()->create([
            'file_path'     => 'service_requests/passport-scan.pdf',
            'original_name' => 'passport-scan.pdf',
            'file_size'     => 12,
            'visibility'    => 'all', // the default "client visible" level
        ]);
        Storage::disk('public')->put('service_requests/passport-scan.pdf', 'secret');

        $this->actingAs($this->clientB)
            ->get("/attachments/{$attachment->id}/download")
            ->assertForbidden();

        // The owner may still download their own file
        $this->actingAs($this->clientA)
            ->get("/attachments/{$attachment->id}/download")
            ->assertOk();
    }

    public function test_client_cannot_comment_on_another_clients_request(): void
    {
        $this->actingAs($this->clientB)
            ->post("/service-requests/{$this->requestA->id}/comments", [
                'content'    => 'Snooping comment',
                'visibility' => 'all',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('stage_comments', [
            'service_request_id' => $this->requestA->id,
            'content'            => 'Snooping comment',
        ]);
    }

    public function test_client_can_view_own_request(): void
    {
        $this->actingAs($this->clientA)
            ->get("/service-requests/{$this->requestA->id}")
            ->assertOk()
            ->assertSee($this->requestA->title);
    }

    public function test_admin_can_view_any_request(): void
    {
        $this->actingAs($this->admin)
            ->get("/service-requests/{$this->requestA->id}")
            ->assertOk();

        $this->actingAs($this->admin)
            ->get("/service-requests/{$this->requestB->id}")
            ->assertOk();
    }

    public function test_dashboard_shows_only_own_activity_for_clients(): void
    {
        \App\Models\ActivityLog::create([
            'user'         => $this->clientA->id,
            'action'       => 'created',
            'subject_type' => ServiceRequest::class,
            'subject_id'   => $this->requestA->id,
            'changes'      => ['title' => $this->requestA->title],
        ]);
        \App\Models\ActivityLog::create([
            'user'         => $this->clientB->id,
            'action'       => 'created',
            'subject_type' => ServiceRequest::class,
            'subject_id'   => $this->requestB->id,
            'changes'      => ['title' => $this->requestB->title],
        ]);

        $response = $this->actingAs($this->clientB)->get('/dashboard');
        $response->assertOk();

        $response->assertDontSee($this->requestA->title);
        $response->assertDontSee($this->clientA->name);
    }
}
