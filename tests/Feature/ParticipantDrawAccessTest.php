<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\OfficialAccessSetting;
use App\Models\Participant;
use App\Models\UserCategoryAccess;
use App\Models\User;
use App\Models\UserDistrictAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParticipantDrawAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_panitia_can_open_lot_draw(): void
    {
        [$participant, $panitia] = $this->createVerifiedParticipantAndUsers(false);

        UserCategoryAccess::query()->create([
            'user_id' => $panitia->id,
            'competition_category_id' => $participant->competition_category_id,
        ]);

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
            ->assertOk();
    }

    public function test_official_cannot_open_maqra_draw_when_maqra_is_closed_but_can_open_when_enabled(): void
    {
        [$participant, ,] = $this->createVerifiedParticipantAndUsers(true);

        $official = User::factory()->create([
            'role' => 'official',
            'district_id' => $participant->district_id,
        ]);

        OfficialAccessSetting::query()->create([
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
            'participant_verification_open' => true,
            'participant_lot_open' => true,
            'participant_maqra_open' => false,
            'participant_maqra_category_ids' => [$participant->competition_category_id],
        ]);

        $this->actingAs($official)
            ->get(route('participants.maqra.draw', $participant))
            ->assertForbidden();

        OfficialAccessSetting::query()->create([
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
            'participant_verification_open' => true,
            'participant_lot_open' => true,
            'participant_maqra_open' => true,
            'participant_maqra_category_ids' => [$participant->competition_category_id],
        ]);

        $this->actingAs($official)
            ->get(route('participants.maqra.draw', $participant))
            ->assertOk();
    }

    public function test_fahmil_participants_share_lot_in_groups_of_three(): void
    {
        Storage::fake('public');

        $panitia = User::factory()->create([
            'role' => 'panitia',
            'district_id' => null,
        ]);

        $district = District::query()->create([
            'name' => 'Kecamatan Lima Kaum',
            'slug' => 'lima-kaum-'.uniqid(),
        ]);

        $category = CompetitionCategory::query()->create([
            'branch' => 'Fahmil Qur`an',
            'name' => 'Putri',
            'slug' => 'fahmil-putri-'.uniqid(),
            'quota' => 3,
            'age_requirement' => 'Minimal 13 tahun 0 bulan 0 hari',
            'sort_order' => 1,
        ]);

        UserCategoryAccess::query()->create([
            'user_id' => $panitia->id,
            'competition_category_id' => $category->id,
        ]);

        $participants = collect(range(1, 4))->map(function (int $index) use ($category, $district): Participant {
            return Participant::query()->create([
                'district_id' => $district->id,
                'competition_category_id' => $category->id,
                'registration_number' => 'REG-FHM-0'.$index,
                'participant_role' => 'main',
                'name' => 'Peserta Fahmil '.$index,
                'gender' => 'putri',
                'nik' => '1302062404731'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'ktp_date' => '2026-01-01',
                'place_of_birth' => 'Tanah Datar',
                'date_of_birth' => '2008-01-01',
                'kk_number' => 'KK-FHM-0'.$index,
                'kk_date' => '2026-01-01',
                'phone' => '08123456789'.$index,
                'institution' => 'Pesantren Fahmil',
                'last_education' => 'SMA',
                'current_address' => 'Lima Kaum',
                'ktp_address' => 'Lima Kaum',
                'ktp_district' => 'Lima Kaum',
                'ktp_regency' => 'Tanah Datar',
                'status' => 'active',
                'verification_status' => 'verified',
            ]);
        });

        $this->actingAs($panitia)->post(route('participants.lot.assign', $participants[0]))->assertRedirect();
        $this->actingAs($panitia)->post(route('participants.lot.assign', $participants[1]))->assertRedirect();
        $this->actingAs($panitia)->post(route('participants.lot.assign', $participants[2]))->assertRedirect();
        $this->actingAs($panitia)->post(route('participants.lot.assign', $participants[3]))->assertRedirect();

        $participants = $participants->map->fresh();

        $this->assertSame($participants[0]->lot_number, $participants[1]->lot_number);
        $this->assertSame($participants[0]->lot_number, $participants[2]->lot_number);
        $this->assertNotSame($participants[0]->lot_number, $participants[3]->lot_number);
    }

    public function test_khutbah_participants_share_lot_in_groups_of_two(): void
    {
        Storage::fake('public');

        $panitia = User::factory()->create([
            'role' => 'panitia',
            'district_id' => null,
        ]);

        $district = District::query()->create([
            'name' => 'Kecamatan Sungai Tarab',
            'slug' => 'sungai-tarab-'.uniqid(),
        ]);

        $category = CompetitionCategory::query()->create([
            'branch' => 'Khutbah Jumat dan Adzan',
            'name' => 'Putra',
            'slug' => 'khutbah-putra-'.uniqid(),
            'quota' => 2,
            'age_requirement' => 'Minimal 13 tahun 0 bulan 0 hari',
            'sort_order' => 1,
        ]);

        UserCategoryAccess::query()->create([
            'user_id' => $panitia->id,
            'competition_category_id' => $category->id,
        ]);

        $participants = collect(range(1, 3))->map(function (int $index) use ($category, $district): Participant {
            return Participant::query()->create([
                'district_id' => $district->id,
                'competition_category_id' => $category->id,
                'registration_number' => 'REG-KHT-0'.$index,
                'participant_role' => 'main',
                'name' => 'Peserta Khutbah '.$index,
                'gender' => 'putra',
                'nik' => '1302062404732'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                'ktp_date' => '2026-01-01',
                'place_of_birth' => 'Tanah Datar',
                'date_of_birth' => '2008-01-01',
                'kk_number' => 'KK-KHT-0'.$index,
                'kk_date' => '2026-01-01',
                'phone' => '08123456788'.$index,
                'institution' => 'Pesantren Khutbah',
                'last_education' => 'SMA',
                'current_address' => 'Sungai Tarab',
                'ktp_address' => 'Sungai Tarab',
                'ktp_district' => 'Sungai Tarab',
                'ktp_regency' => 'Tanah Datar',
                'status' => 'active',
                'verification_status' => 'verified',
            ]);
        });

        $this->actingAs($panitia)->post(route('participants.lot.assign', $participants[0]))->assertRedirect();
        $this->actingAs($panitia)->post(route('participants.lot.assign', $participants[1]))->assertRedirect();
        $this->actingAs($panitia)->post(route('participants.lot.assign', $participants[2]))->assertRedirect();

        $participants = $participants->map->fresh();

        $this->assertSame($participants[0]->lot_number, $participants[1]->lot_number);
        $this->assertNotSame($participants[0]->lot_number, $participants[2]->lot_number);
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
