<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\Participant;
use App\Models\User;
use App\Models\UserDistrictAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficialEditAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_cannot_edit_participant_after_submitted(): void
    {
        [$participant, $official] = $this->createParticipantAndOfficial('submitted');

        $this->actingAs($official)
            ->get(route('participants.edit', $participant))
            ->assertForbidden();
    }

    public function test_official_can_edit_participant_when_still_draft(): void
    {
        [$participant, $official] = $this->createParticipantAndOfficial('draft');

        $this->actingAs($official)
            ->get(route('participants.edit', $participant))
            ->assertOk();
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
            'slug' => 'remaja-putra',
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
            'registration_number' => 'REG-EDIT-001',
            'participant_role' => 'main',
            'name' => 'Peserta Uji Edit',
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
}
