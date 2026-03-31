<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalMessagesTest extends TestCase
{
    use RefreshDatabase;

    private function makePortalUser(): array
    {
        $user   = User::factory()->create();
        $client = Client::create([
            'company_name'          => 'Message Test Ltd',
            'primary_contact_name'  => 'Alice Smith',
            'primary_contact_email' => $user->email,
            'portal_user_id'        => $user->id,
        ]);

        return [$user, $client];
    }

    private function makeProject(Client $client): Project
    {
        return Project::create([
            'title'     => 'Test Project',
            'client_id' => $client->id,
            'status'    => 'active',
        ]);
    }

    // -----------------------------------------------------------------------
    // Access control
    // -----------------------------------------------------------------------

    public function test_unauthenticated_user_cannot_send_message(): void
    {
        [$user, $client] = $this->makePortalUser();
        $project = $this->makeProject($client);

        $this->post(route('portal.messages.store', $project), ['content' => 'Hello'])
            ->assertRedirect(route('login'));
    }

    public function test_client_cannot_message_another_clients_project(): void
    {
        [$user,  $client]  = $this->makePortalUser();
        [$user2, $client2] = $this->makePortalUser();

        $project = $this->makeProject($client2);

        $this->actingAs($user)
            ->post(route('portal.messages.store', $project), ['content' => 'Hack attempt'])
            ->assertForbidden();
    }

    // -----------------------------------------------------------------------
    // Sending messages
    // -----------------------------------------------------------------------

    public function test_client_can_send_a_message(): void
    {
        [$user, $client] = $this->makePortalUser();
        $project = $this->makeProject($client);

        $this->actingAs($user)
            ->post(route('portal.messages.store', $project), [
                'content' => 'Hi, any update on the project?',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_messages', [
            'project_id'  => $project->id,
            'sender_type' => Client::class,
            'sender_id'   => $client->id,
            'content'     => 'Hi, any update on the project?',
        ]);
    }

    public function test_message_content_is_required(): void
    {
        [$user, $client] = $this->makePortalUser();
        $project = $this->makeProject($client);

        $this->actingAs($user)
            ->post(route('portal.messages.store', $project), ['content' => ''])
            ->assertSessionHasErrors('content');
    }

    public function test_message_content_cannot_exceed_5000_characters(): void
    {
        [$user, $client] = $this->makePortalUser();
        $project = $this->makeProject($client);

        $this->actingAs($user)
            ->post(route('portal.messages.store', $project), [
                'content' => str_repeat('a', 5001),
            ])
            ->assertSessionHasErrors('content');
    }

    public function test_multiple_messages_are_stored_in_order(): void
    {
        [$user, $client] = $this->makePortalUser();
        $project = $this->makeProject($client);

        $this->actingAs($user)->post(route('portal.messages.store', $project), ['content' => 'First message']);
        $this->actingAs($user)->post(route('portal.messages.store', $project), ['content' => 'Second message']);

        $messages = ProjectMessage::where('project_id', $project->id)->orderBy('created_at')->get();

        $this->assertCount(2, $messages);
        $this->assertEquals('First message', $messages->first()->content);
        $this->assertEquals('Second message', $messages->last()->content);
    }

    // -----------------------------------------------------------------------
    // Project show — messages are visible
    // -----------------------------------------------------------------------

    public function test_project_show_includes_messages(): void
    {
        [$user, $client] = $this->makePortalUser();
        $project = $this->makeProject($client);

        ProjectMessage::create([
            'project_id'  => $project->id,
            'sender_type' => Client::class,
            'sender_id'   => $client->id,
            'content'     => 'Visible message',
        ]);

        $this->actingAs($user)
            ->get(route('portal.projects.show', $project))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('project'));
    }

    public function test_viewing_project_marks_agency_messages_as_read(): void
    {
        [$user, $client] = $this->makePortalUser();
        $project = $this->makeProject($client);

        $agencyUser = User::factory()->create();

        // Agency message that is unread
        $msg = ProjectMessage::create([
            'project_id'  => $project->id,
            'sender_type' => User::class,
            'sender_id'   => $agencyUser->id,
            'content'     => 'Agency update for you',
            'read_at'     => null,
        ]);

        $this->actingAs($user)
            ->get(route('portal.projects.show', $project))
            ->assertOk();

        // Message from agency side should now be marked read
        $this->assertNotNull($msg->fresh()->read_at);
    }
}
