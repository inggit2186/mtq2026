<?php

namespace Tests\Feature;

use App\Models\ArchivedParticipant;
use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchivedParticipantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_fails_when_active_nik_already_exists(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = $this->createCategory();
        $district = $this->createDistrict();

        $archivedParticipant = $this->archiveParticipant([
            'competition_category_id' => $category->id,
            'district_id' => $district->id,
            'nik' => '1302062404730003',
            'registration_number' => 'REG-260511-A1',
        ], $admin);

        Participant::query()->create([
            'competition_category_id' => $category->id,
            'district_id' => $district->id,
            'registration_number' => 'REG-260511-B2',
            'participant_role' => 'main',
            'name' => 'Peserta Aktif',
            'gender' => 'putra',
            'nik' => '1302062404730003',
            'institution' => 'Masjid Raya',
            'status' => 'active',
            'verification_status' => 'draft',
        ]);

        $this->actingAs($admin)
            ->from(route('participants.trash'))
            ->post(route('participants.restore', ['participant' => $archivedParticipant->id]))
            ->assertRedirect(route('participants.trash'))
            ->assertSessionHasErrors(['participant']);

        $this->assertDatabaseHas('archived_participants', ['id' => $archivedParticipant->id]);
        $this->assertDatabaseHas('participants', ['nik' => '1302062404730003', 'registration_number' => 'REG-260511-B2']);
    }

    public function test_admin_can_permanently_delete_archived_participant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = $this->createCategory();
        $district = $this->createDistrict();

        $archivedParticipant = $this->archiveParticipant([
            'competition_category_id' => $category->id,
            'district_id' => $district->id,
            'nik' => '1302062404730011',
            'registration_number' => 'REG-260511-C3',
        ], $admin);

        $this->actingAs($admin)
            ->post(route('participants.trash.destroy', ['participant' => $archivedParticipant->id]))
            ->assertRedirect(route('participants.trash'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('archived_participants', ['id' => $archivedParticipant->id]);
        $this->assertDatabaseMissing('participants', ['registration_number' => 'REG-260511-C3']);
    }

    public function test_admin_can_import_legacy_soft_deleted_participant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $category = $this->createCategory();
        $district = $this->createDistrict();

        $participant = Participant::query()->create([
            'competition_category_id' => $category->id,
            'district_id' => $district->id,
            'registration_number' => 'REG-260511-D4',
            'participant_role' => 'main',
            'name' => 'Peserta Lama',
            'gender' => 'putri',
            'nik' => '1302062404730099',
            'institution' => 'Pesantren Lama',
            'status' => 'active',
            'verification_status' => 'submitted',
        ]);
        $participant->delete();

        $this->actingAs($admin)
            ->post(route('participants.trash.import-legacy'))
            ->assertRedirect(route('participants.trash'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('participants', ['id' => $participant->id]);
        $this->assertDatabaseHas('archived_participants', [
            'source_participant_id' => $participant->id,
            'registration_number' => 'REG-260511-D4',
        ]);
    }

    protected function archiveParticipant(array $overrides, User $admin): ArchivedParticipant
    {
        $participant = Participant::query()->create(array_merge([
            'competition_category_id' => $overrides['competition_category_id'],
            'district_id' => $overrides['district_id'] ?? null,
            'registration_number' => $overrides['registration_number'],
            'participant_role' => 'main',
            'name' => 'Peserta Arsip',
            'gender' => 'putra',
            'nik' => $overrides['nik'],
            'institution' => 'Pesantren MTQ',
            'status' => 'active',
            'verification_status' => 'submitted',
        ], $overrides));

        $this->actingAs($admin)
            ->post(route('participants.archive', ['participant' => $participant->id]))
            ->assertRedirect(route('participants.list'));

        return ArchivedParticipant::query()->firstOrFail();
    }

    protected function createCategory(): CompetitionCategory
    {
        return CompetitionCategory::query()->create([
            'branch' => 'Tilawah',
            'name' => 'Anak-Anak Putra',
            'slug' => 'tilawah-anak-putra-'.uniqid(),
            'round' => 'Penyisihan',
            'color' => '#14b8a6',
        ]);
    }

    protected function createDistrict(): District
    {
        return District::query()->create([
            'name' => 'Lintau Buo',
            'slug' => 'lintau-buo-'.uniqid(),
        ]);
    }
}
