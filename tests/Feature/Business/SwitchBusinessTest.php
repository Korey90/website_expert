<?php

namespace Tests\Feature\Business;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SwitchBusinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_member_can_switch_workspace(): void
    {
        $user = User::factory()->create();
        $first = $this->business('First workspace');
        $second = $this->business('Sandbox workspace');
        $this->membership($first, $user);
        $this->membership($second, $user);

        $this->actingAs($user)
            ->post(route('business.switch', $second))
            ->assertRedirect('/admin')
            ->assertSessionHas(User::CURRENT_BUSINESS_SESSION_KEY, $second->id);

        $this->assertTrue($user->fresh()->currentBusiness()->is($second));
    }

    public function test_user_cannot_switch_to_workspace_without_active_membership(): void
    {
        $user = User::factory()->create();
        $first = $this->business('First workspace');
        $forbidden = $this->business('Forbidden workspace');
        $this->membership($first, $user);

        $this->actingAs($user)
            ->withSession([User::CURRENT_BUSINESS_SESSION_KEY => $first->id])
            ->post(route('business.switch', $forbidden))
            ->assertForbidden()
            ->assertSessionHas(User::CURRENT_BUSINESS_SESSION_KEY, $first->id);
    }

    public function test_invalid_session_selection_is_cleared_and_first_active_workspace_is_used(): void
    {
        $user = User::factory()->create();
        $first = $this->business('First workspace');
        $inactive = $this->business('Inactive workspace');
        $this->membership($first, $user);
        $this->membership($inactive, $user, false);

        $this->actingAs($user)
            ->withSession([User::CURRENT_BUSINESS_SESSION_KEY => $inactive->id])
            ->get('/dashboard')
            ->assertRedirect('/admin');

        $this->assertTrue($user->fresh()->currentBusiness()->is($first));
        $this->assertNull(session(User::CURRENT_BUSINESS_SESSION_KEY));
    }

    private function business(string $name): Business
    {
        return Business::create([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'is_active' => true,
        ]);
    }

    private function membership(Business $business, User $user, bool $active = true): BusinessUser
    {
        return BusinessUser::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => $active,
            'joined_at' => now(),
        ]);
    }
}
