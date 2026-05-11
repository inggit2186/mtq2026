<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\OfficialAccessSetting;
use App\Models\Participant;
use App\Models\User;
use App\Models\UserDistrictAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantVerificationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_panitia_cannot_verify_when_verification_is_closed(): void
    {
        [$participant, $panitia] = $this->createParticipantAndPanitia();

        OfficialAccessSetting::query()->create([
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
            'participant_verification_open' => false,
        ]);

        $response = $this->actingAs($panitia)->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('Masa verifikasi peserta untuk panitia sedang ditutup oleh admin.');
        $response->assertDontSee('Form verifikasi');

        $this->actingAs($panitia)
            ->post(route('participants.verify', $participant), [
                'verification_status' => 'verified',
                'verification_notes' => 'Cukup lengkap',
            ])
            ->assertForbidden();
    }

    public function test_admin_can_verify_even_when_verification_is_closed_for_panitia(): void
    {
        [$participant] = $this->createParticipantAndPanitia();

        OfficialAccessSetting::query()->create([
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
            'participant_verification_open' => false,
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->post(route('participants.verify', $participant), [
                'verification_status' => 'verified',
                'verification_notes' => 'Disetujui oleh admin',
            ])
            ->assertRedirect(route('participants.list'));

        $participant->refresh();

        $this->assertSame('verified', $participant->verification_status);
    }

    /**
     * @return array{0: \App\Models\Participant, 1: \App\Models\User}
     */
    private function createParticipantAndPanitia(): array
    {
        $district = District::query()->create([
            'name' => 'Kecamatan Pariangan',
            'slug' => 'pariangan',
        ]);

        $category = CompetitionCategory::query()->create([
            'branch' => 'Tilawah',
            'name' => 'Remaja Putra',
            'slug' => 'remaja-putra',
            'quota' => 1,
            'age_requirement' => 'Minimal 13 tahun 0 bulan 0 hari',
            'sort_order' => 1,
        ]);

        $panitia = User::factory()->create([
            'role' => 'panitia',
            'district_id' => null,
        ]);

        UserDistrictAccess::query()->create([
            'user_id' => $panitia->id,
            'district_id' => $district->id,
        ]);

        $participant = Participant::query()->create([
            'district_id' => $district->id,
            'competition_category_id' => $category->id,
            'registration_number' => 'REG-VER-001',
            'participant_role' => 'main',
            'name' => 'Peserta Uji Verifikasi',
            'gender' => 'putra',
            'nik' => '1234567890123456',
            'ktp_date' => '2026-01-01',
            'place_of_birth' => 'Tanah Datar',
            'date_of_birth' => '2008-01-01',
            'kk_number' => 'KK001',
            'kk_date' => '2026-01-01',
            'phone' => '081234567890',
            'institution' => 'TPQ Pariangan',
            'last_education' => 'SMA',
            'current_address' => 'Pariangan',
            'ktp_address' => 'Pariangan',
            'ktp_district' => 'Pariangan',
            'ktp_regency' => 'Tanah Datar',
            'status' => 'active',
            'verification_status' => 'submitted',
        ]);

        return [$participant, $panitia];
    }
}
