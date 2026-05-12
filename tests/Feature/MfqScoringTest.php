<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Participant;
use App\Models\ScoreEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MfqScoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_panitia_page_shows_mfq_form(): void
    {
        [$admin, $participants] = $this->createMfqParticipantFixture();

        $response = $this->actingAs($admin)
            ->withSession([
                'mfq.selection' => [
                    'competition_category_id' => $participants[0]->competition_category_id,
                    'participant_ids' => collect($participants)->pluck('id')->all(),
                ],
            ])
            ->get(route('scoring.mfq', [
                'participant_id' => $participants[0]->id,
                'competition_category_id' => $participants[0]->competition_category_id,
            ]));

        $response->assertOk();
        $response->assertSee('Tahap 2: nilai regu yang sudah dipilih');
        $response->assertSee('Grid skor ala Excel');
        $response->assertSee($participants[0]->name);
    }

    public function test_mfq_score_submission_calculates_total_and_saves_breakdown(): void
    {
        [$admin, $participants] = $this->createMfqParticipantFixture();
        $activeParticipant = $participants[0];

        $questions = [];
        for ($i = 1; $i <= 15; $i++) {
            $questions[] = [
                'label' => 'Soal '.$i,
                'package_score' => $i === 1 ? '100' : '',
                'throw_scores' => [''],
                'rebuttal_score' => '',
                'notes' => 'Catatan '.$i,
            ];
        }

        $response = $this->actingAs($admin)
            ->withSession([
                'mfq.selection' => [
                    'competition_category_id' => $activeParticipant->competition_category_id,
                    'participant_ids' => collect($participants)->pluck('id')->all(),
                ],
            ])
            ->post(route('scoring.mfq.store'), [
                'participant_id' => $activeParticipant->id,
                'judge_name' => 'Hakim MFQ',
                'judging_round' => 'Penyisihan',
                'remarks' => 'Penilaian percobaan MFQ',
                'questions' => $questions,
            ]);

        $response->assertRedirect(route('scoring.mfq', [
            'participant_id' => $activeParticipant->id,
            'competition_category_id' => $activeParticipant->competition_category_id,
            'judging_round' => 'Penyisihan',
        ]));

        $scoreEntry = ScoreEntry::query()->latest('id')->first();

        $this->assertNotNull($scoreEntry);
        $this->assertSame('Hakim MFQ', $scoreEntry->judge_name);
        $this->assertSame('Penyisihan', $scoreEntry->judging_round);
        $this->assertSame(100.0, (float) $scoreEntry->score);
        $this->assertSame('MFQ', $scoreEntry->score_breakdown['type']);
        $this->assertCount(15, $scoreEntry->score_breakdown['questions']);
        $this->assertSame(15, $scoreEntry->score_breakdown['summary']['total_questions']);
        $this->assertSame(100.0, (float) $scoreEntry->score_breakdown['summary']['total_score']);
        $this->assertSame(15, (int) $scoreEntry->score_breakdown['sheet_style']['question_count']);
        $this->assertSame($activeParticipant->name, $scoreEntry->score_breakdown['sheet_style']['active_regu_name']);
    }

    /**
     * @return array{0: \App\Models\User, 1: array<int, \App\Models\Participant>}
     */
    private function createMfqParticipantFixture(): array
    {
        $category = CompetitionCategory::query()->create([
            'branch' => 'Fahmil Qur\'an',
            'name' => 'Putra',
            'slug' => 'fahmil-putra',
            'round' => 'Penyisihan',
            'color' => '#14b8a6',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $participantA = Participant::query()->create([
            'competition_category_id' => $category->id,
            'registration_number' => 'MFQ-001',
            'name' => 'Regu MFQ Uji Coba A',
            'institution' => 'MTs Negeri Contoh',
            'status' => 'active',
            'verification_status' => 'verified',
        ]);

        $participantB = Participant::query()->create([
            'competition_category_id' => $category->id,
            'registration_number' => 'MFQ-002',
            'name' => 'Regu MFQ Uji Coba B',
            'institution' => 'MTs Negeri Contoh',
            'status' => 'active',
            'verification_status' => 'verified',
        ]);

        return [$admin, [$participantA, $participantB]];
    }
}
