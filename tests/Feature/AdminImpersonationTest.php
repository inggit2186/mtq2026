<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_as_other_user_by_id_and_return_back(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $target = User::factory()->create([
            'role' => 'official',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.impersonate.store'), [
                'identifier' => (string) $target->id,
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($target);
        $this->assertSame($admin->id, session('impersonation.original_user_id'));
        $this->assertSame($target->id, session('impersonation.target_user_id'));

        $this->post(route('admin.impersonate.stop'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertSessionMissing('impersonation');
    }

    public function test_non_admin_cannot_start_impersonation(): void
    {
        $user = User::factory()->create([
            'role' => 'panitia',
        ]);

        $target = User::factory()->create([
            'role' => 'official',
        ]);

        $this->actingAs($user)
            ->post(route('admin.impersonate.store'), [
                'identifier' => (string) $target->id,
            ])
            ->assertForbidden();
    }
}
