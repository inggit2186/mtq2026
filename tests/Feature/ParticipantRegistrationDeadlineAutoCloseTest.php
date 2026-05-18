<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\OfficialAccessSetting;
use App\Models\Participant;
use App\Models\User;
use App\Models\UserDistrictAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ParticipantRegistrationDeadlineAutoCloseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_official_registration_is_closed_at_midnight_after_juknis_deadline(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 19, 0, 0, 0, 'Asia/Bangkok'));
        config(['juknis.registration.close' => '18 Mei 2026']);

        $official = $this->createOfficialUser();
        $category = $this->createCategory();
        $this->createOpenAccessSetting();

        $this->actingAs($official)
            ->post(route('participants.store'), [
                'district_id' => $official->district_id,
                'competition_category_id' => $category->id,
                'participant_role' => 'main',
                'name' => 'Peserta Batas Waktu',
                'gender' => 'putra',
                'nik' => '1302062404731000',
                'place_of_birth' => 'Tanah Datar',
                'date_of_birth' => '2008-01-01',
                'kk_number' => 'KK-001',
                'kk_date' => '2026-01-01',
                'phone' => '081234567890',
                'institution' => 'TPQ Pariangan',
                'last_education' => 'SMA',
                'current_address' => 'Pariangan',
                'ktp_address' => 'Pariangan',
                'ktp_district' => 'Pariangan',
                'ktp_regency' => 'Tanah Datar',
                'submit_action' => 'draft',
            ])
            ->assertForbidden();
    }

    public function test_official_edit_is_closed_at_midnight_after_juknis_deadline(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 19, 0, 0, 0, 'Asia/Bangkok'));
        config(['juknis.registration.close' => '18 Mei 2026']);

        [$participant, $official] = $this->createParticipantAndOfficial();
        $this->createOpenAccessSetting();

        $this->actingAs($official)
            ->get(route('participants.edit', $participant))
            ->assertForbidden();
    }

    public function test_official_delete_is_closed_at_midnight_after_juknis_deadline(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 5, 19, 0, 0, 0, 'Asia/Bangkok'));
        config(['juknis.registration.close' => '18 Mei 2026']);

        [$participant, $official] = $this->createParticipantAndOfficial();
        $this->createOpenAccessSetting();

        $this->actingAs($official)
            ->post(route('participants.archive', $participant))
            ->assertForbidden();
    }

    /**
     * @return array{0: \App\Models\Participant, 1: \App\Models\User}
     */
    private function createParticipantAndOfficial(): array
    {
        $district = District::query()->create([
            'name' => 'Kecamatan Pariangan',
            'slug' => 'pariangan-'.uniqid(),
        ]);

        $category = $this->createCategory();

        $official = $this->createOfficialUser($district->id);

        $participant = Participant::query()->create([
            'district_id' => $district->id,
            'competition_category_id' => $category->id,
            'registration_number' => 'REG-DEADLINE-'.uniqid(),
            'participant_role' => 'main',
            'name' => 'Peserta Uji Deadline',
            'gender' => 'putra',
            'nik' => '1302062404732000',
            'place_of_birth' => 'Tanah Datar',
            'date_of_birth' => '2008-01-01',
            'kk_number' => 'KK-002',
            'kk_date' => '2026-01-01',
            'phone' => '081234567891',
            'institution' => 'TPQ Pariangan',
            'last_education' => 'SMA',
            'current_address' => 'Pariangan',
            'ktp_address' => 'Pariangan',
            'ktp_district' => 'Pariangan',
            'ktp_regency' => 'Tanah Datar',
            'status' => 'active',
            'verification_status' => 'submitted',
        ]);

        return [$participant, $official];
    }

    private function createOfficialUser(?int $districtId = null): User
    {
        $districtId = $districtId ?? District::query()->create([
            'name' => 'Kecamatan Pariangan',
            'slug' => 'pariangan-user-'.uniqid(),
        ])->id;

        $official = User::factory()->create([
            'role' => 'official',
            'district_id' => $districtId,
        ]);

        UserDistrictAccess::query()->create([
            'user_id' => $official->id,
            'district_id' => $districtId,
        ]);

        return $official;
    }

    private function createCategory(): CompetitionCategory
    {
        return CompetitionCategory::query()->create([
            'branch' => 'Tilawah',
            'name' => 'Remaja Putra',
            'slug' => 'remaja-putra-'.uniqid(),
            'quota' => 1,
            'age_requirement' => 'Minimal 13 tahun 0 bulan 0 hari',
            'sort_order' => 1,
        ]);
    }

    private function createOpenAccessSetting(): void
    {
        OfficialAccessSetting::query()->create([
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'participant_delete_open' => true,
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
