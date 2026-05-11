<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoginPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_forgot_password_sends_new_password_to_whatsapp(): void
    {
        config()->set('services.whatsapp.base_url', 'https://wa.example.test');
        config()->set('services.whatsapp.api_key', 'test-key');
        config()->set('services.whatsapp.sender', 'emtq');

        Http::fake([
            'https://wa.example.test/send-message' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create([
            'role' => 'official',
            'nomor_induk' => '1234567890123456',
            'phone' => '081234567890',
            'must_change_password' => false,
        ]);

        $oldPasswordHash = $user->getRawOriginal('password');

        $response = $this->post(route('password.reset.request'), [
            'nip' => '1234567890123456',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $user->refresh();

        $this->assertNotSame($oldPasswordHash, $user->getRawOriginal('password'));
        $this->assertTrue((bool) $user->must_change_password);

        Http::assertSentCount(1);
        Http::assertSent(function ($request): bool {
            return str_contains((string) $request->url(), '/send-message')
                && str_contains((string) ($request['message'] ?? ''), 'Password Baru');
        });
    }
}
