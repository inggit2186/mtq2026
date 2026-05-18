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

class ParticipantDeleteAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_cannot_archive_participant_when_delete_access_is_closed(): void
    {
        [$participant, $official] = $this->createParticipantAndOfficial('submitted');
        $this->createOfficialAccessSetting(false);

        $this->actingAs($official)
            ->post(route('participants.archive', $participant))
            ->assertForbidden();

        $this->assertDatabaseHas('participants', ['id' => $participant->id]);
        $this->assertDatabaseMissing('archived_participants', ['source_participant_id' => $participant->id]);
    }

    public function test_panitia_can_archive_participant_when_delete_access_is_open(): void
    {
        [$participant, $panitia] = $this->createParticipantAndPanitia('verified');
        $this->createOfficialAccessSetting(true);

        $this->actingAs($panitia)
            ->post(route('participants.archive', $participant))
            ->assertRedirect(route('participants.list'));

        $this->assertDatabaseMissing('participants', ['id' => $participant->id]);
        $this->assertDatabaseHas('archived_participants', ['source_participant_id' => $participant->id]);
    }

    public function test_admin_can_archive_participant_even_when_delete_access_is_closed(): void
    {
        [$participant] = $this->createParticipantAndOfficial('verified');
        $this->createOfficialAccessSetting(false);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('participants.archive', $participant))
            ->assertRedirect(route('participants.list'));

        $this->assertDatabaseMissing('participants', ['id' => $participant->id]);
        $this->assertDatabaseHas('archived_participants', ['source_participant_id' => $participant->id]);
    }

    /**
     * @return array{0: \App\Models\Participant, 1: \App\Models\User}
     */
    private function createParticipantAndOfficial(string $status): array
    {
        $district = District::query()->create([
            'name' => 'Kecamatan Pariangan',
            'slug' => 'pariangan',
        ]);

        $category = CompetitionCategory::query()->create([
            'branch' => 'Tilawah',
            'name' => 'Remaja Putra',
            'slug' => 'remaja-putra-'.uniqid(),
            'quota' => 1,
            'age_requirement' => 'Minimal 13 tahun 0 bulan 0 hari',
            'sort_order' => 1,
        ]);

        $official = User::factory()->create([
            'role' => 'official',
            'district_id' => $district->id,
        ]);

        UserDistrictAccess::query()->create([
            'user_id' => $official->id,
            'district_id' => $district->id,
        ]);

        $participant = Participant::query()->create([
            'district_id' => $district->id,
            'competition_category_id' => $category->id,
            'registration_number' => 'REG-DEL-'.uniqid(),
            'participant_role' => 'main',
            'name' => 'Peserta Uji Hapus',
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
            'verification_status' => $status,
        ]);

        return [$participant, $official];
    }

    /**
     * @return array{0: \App\Models\Participant, 1: \App\Models\User}
     */
    private function createParticipantAndPanitia(string $status): array
    {
        $district = District::query()->create([
            'name' => 'Kecamatan Pariangan',
            'slug' => 'pariangan-panitia',
        ]);

        $category = CompetitionCategory::query()->create([
            'branch' => 'Tilawah',
            'name' => 'Remaja Putra',
            'slug' => 'remaja-putra-panitia-'.uniqid(),
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
            'registration_number' => 'REG-DEL-P-'.uniqid(),
            'participant_role' => 'main',
            'name' => 'Peserta Uji Hapus Panitia',
            'gender' => 'putra',
            'nik' => '1234567890123457',
            'ktp_date' => '2026-01-01',
            'place_of_birth' => 'Tanah Datar',
            'date_of_birth' => '2008-01-01',
            'kk_number' => 'KK002',
            'kk_date' => '2026-01-01',
            'phone' => '081234567891',
            'institution' => 'TPQ Pariangan',
            'last_education' => 'SMA',
            'current_address' => 'Pariangan',
            'ktp_address' => 'Pariangan',
            'ktp_district' => 'Pariangan',
            'ktp_regency' => 'Tanah Datar',
            'status' => 'active',
            'verification_status' => $status,
        ]);

        return [$participant, $panitia];
    }

    private function createOfficialAccessSetting(bool $deleteOpen): void
    {
        OfficialAccessSetting::query()->create([
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'participant_delete_open' => $deleteOpen,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
            'participant_verification_open' => true,
            'participant_lot_open' => true,
            'participant_maqra_open' => true,
            'participant_maqra_penyisihan_open' => true,
            'participant_maqra_final_open' => true,
            'participant_maqra_lot_min' => null,
            'participant_maqra_lot_max' => null,
            'participant_maqra_lot_ranges' => [],
            'participant_maqra_category_ids' => [],
        ]);
    }
}
