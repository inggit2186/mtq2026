<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAnnouncementTargetingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_targeted_dashboard_notice_and_only_target_roles_can_see_it(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $district = District::query()->create([
            'name' => 'Kecamatan Pariangan',
            'slug' => 'pariangan-'.uniqid(),
        ]);
        $official = User::factory()->create([
            'role' => 'official',
            'district_id' => $district->id,
        ]);
        $panitia = User::factory()->create([
            'role' => 'panitia',
        ]);
        $participant = User::factory()->create([
            'role' => 'peserta',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.content.announcements.store'), [
            'title' => 'Notifikasi Dashboard Internal',
            'body' => 'Pesan ini hanya untuk official dan panitia.',
            'priority' => 'normal',
            'audience' => 'official_panitia',
            'published_at' => now()->format('Y-m-d\TH:i'),
        ]);

        $response->assertRedirect(route('admin.content'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('announcements', [
            'title' => 'Notifikasi Dashboard Internal',
            'audience' => 'official_panitia',
            'published_by' => $admin->id,
        ]);

        $this->actingAs($official)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Notifikasi Dashboard')
            ->assertSee('Notifikasi Dashboard Internal')
            ->assertSee('Official + Panitia');

        $this->actingAs($panitia)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Notifikasi Dashboard Internal');

        $this->actingAs($participant)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Notifikasi Dashboard Internal');
    }

    public function test_dashboard_shows_verification_focus_copy(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Countdown Perbaikan Berkas')
            ->assertSee('Progress Perbaikan per Kecamatan')
            ->assertSee('Peserta / Terverifikasi')
            ->assertSee('Butuh Perbaikan');
    }
}
