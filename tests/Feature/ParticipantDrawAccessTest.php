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

class ParticipantDrawAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_panitia_cannot_open_lot_draw_when_lot_is_closed_but_admin_can(): void
    {
        [$participant, $panitia, $admin] = $this->createVerifiedParticipantAndUsers(false);

        OfficialAccessSetting::query()->create([
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
            'participant_verification_open' => true,
            'participant_lot_open' => false,
            'participant_maqra_open' => true,
        ]);

        $this->actingAs($panitia)
            ->get(route('participants.lot.draw', $participant))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('participants.lot.draw', $participant))
            ->assertOk();
    }

    public function test_panitia_cannot_open_maqra_draw_when_maqra_is_closed_but_admin_can(): void
    {
        [$participant, $panitia, $admin] = $this->createVerifiedParticipantAndUsers(true);

        OfficialAccessSetting::query()->create([
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
            'participant_verification_open' => true,
            'participant_lot_open' => true,
            'participant_maqra_open' => false,
        ]);

        $this->actingAs($panitia)
            ->get(route('participants.maqra.draw', $participant))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('participants.maqra.draw', $participant))
            ->assertOk();
    }

    /**
     * @return array{0: \App\Models\Participant, 1: \App\Models\User, 2: \App\Models\User}
     */
    private function createVerifiedParticipantAndUsers(bool $usesMaqra): array
    {
        $district = District::query()->create([
            'name' => 'Kecamatan Pariangan',
            'slug' => 'pariangan',
        ]);

        $category = CompetitionCategory::query()->create([
            'branch' => $usesMaqra ? 'Seni Baca Al Qur\'an' : 'Tilawah',
            'name' => $usesMaqra ? 'Remaja Putra' : 'Remaja Putra',
            'slug' => $usesMaqra ? 'seni-baca-al-quran-remaja-putra' : 'tilawah-remaja-putra',
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

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $participant = Participant::query()->create([
            'district_id' => $district->id,
            'competition_category_id' => $category->id,
            'registration_number' => 'REG-DRW-001',
            'participant_role' => 'main',
            'name' => 'Peserta Uji Ambil',
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
            'verification_status' => 'verified',
            'lot_number' => $usesMaqra ? null : null,
        ]);

        return [$participant, $panitia, $admin];
    }
}
