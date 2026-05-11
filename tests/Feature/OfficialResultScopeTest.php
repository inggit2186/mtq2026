<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\Participant;
use App\Models\ScoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficialResultScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_only_sees_results_for_own_district(): void
    {
        $districtA = District::query()->create([
            'name' => 'Kecamatan Pariangan',
            'slug' => 'pariangan',
        ]);

        $districtB = District::query()->create([
            'name' => 'Kecamatan Rambatan',
            'slug' => 'rambatan',
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
            'district_id' => $districtA->id,
        ]);

        $participantA = Participant::query()->create([
            'district_id' => $districtA->id,
            'competition_category_id' => $category->id,
            'registration_number' => 'REG-A-001',
            'participant_role' => 'main',
            'name' => 'Peserta Pariangan',
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
        ]);

        Participant::query()->create([
            'district_id' => $districtB->id,
            'competition_category_id' => $category->id,
            'registration_number' => 'REG-B-001',
            'participant_role' => 'main',
            'name' => 'Peserta Rambatan',
            'gender' => 'putra',
            'nik' => '2234567890123456',
            'ktp_date' => '2026-01-01',
            'place_of_birth' => 'Tanah Datar',
            'date_of_birth' => '2008-01-01',
            'kk_number' => 'KK002',
            'kk_date' => '2026-01-01',
            'phone' => '081234567891',
            'institution' => 'TPQ Rambatan',
            'last_education' => 'SMA',
            'current_address' => 'Rambatan',
            'ktp_address' => 'Rambatan',
            'ktp_district' => 'Rambatan',
            'ktp_regency' => 'Tanah Datar',
            'status' => 'active',
            'verification_status' => 'verified',
        ]);

        ScoreEntry::query()->create([
            'participant_id' => $participantA->id,
            'judge_name' => 'Juri 1',
            'judging_round' => 'Penyisihan',
            'score' => 85.5,
            'score_breakdown' => ['makhraj' => 20, 'tajwid' => 30, 'lagu' => 35],
            'remarks' => 'Baik',
            'submitted_at' => now(),
        ]);

        ScoreEntry::query()->create([
            'participant_id' => $participantA->id,
            'judge_name' => 'Juri 2',
            'judging_round' => 'Penyisihan',
            'score' => 86.0,
            'score_breakdown' => ['makhraj' => 21, 'tajwid' => 30, 'lagu' => 35],
            'remarks' => 'Stabil',
            'submitted_at' => now()->addMinute(),
        ]);

        $response = $this->actingAs($official)->get(route('results.index'));

        $response->assertOk();
        $response->assertSee('Peserta Pariangan');
        $response->assertDontSee('Peserta Rambatan');
    }

    public function test_official_cannot_open_leaderboard(): void
    {
        $district = District::query()->create([
            'name' => 'Kecamatan Pariangan',
            'slug' => 'pariangan',
        ]);

        $official = User::factory()->create([
            'role' => 'official',
            'district_id' => $district->id,
        ]);

        $this->actingAs($official)
            ->get(route('leaderboard.index'))
            ->assertForbidden();
    }
}
