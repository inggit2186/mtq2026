<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_available(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Portal Login e-MTQ');
    }

    public function test_admin_can_login_successfully(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@emtq.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_peserta_cannot_open_scoring_page(): void
    {
        $user = User::factory()->create([
            'role' => 'peserta',
        ]);

        $response = $this->actingAs($user)->get(route('scoring'));

        $response->assertForbidden();
    }
}
