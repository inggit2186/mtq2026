<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParticipantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_draft_without_required_fields_or_documents(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = CompetitionCategory::query()->create([
            'branch' => 'Tilawah',
            'name' => 'Remaja Putra',
            'slug' => 'remaja-putra-'.uniqid(),
            'quota' => 1,
            'age_requirement' => 'Semua usia',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->post(route('participants.store'), [
            'district_id' => null,
            'competition_category_id' => $category->id,
            'participant_role' => 'main',
            'submit_action' => 'draft',
        ]);

        $response->assertRedirect(route('participants.index'));
        $response->assertSessionHas('status');

        $participant = Participant::query()
            ->where('competition_category_id', $category->id)
            ->where('verification_status', 'draft')
            ->firstOrFail();

        $this->assertNull($participant->name);
        $this->assertNull($participant->institution);
        $this->assertNull($participant->document_kk);
        $this->assertNull($participant->document_birth_certificate);
        $this->assertNull($participant->document_photo);
    }

    public function test_user_cannot_submit_participant_without_required_fields_or_documents(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = CompetitionCategory::query()->create([
            'branch' => 'Tilawah',
            'name' => 'Remaja Putra',
            'slug' => 'remaja-putra-'.uniqid(),
            'quota' => 1,
            'age_requirement' => 'Semua usia',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->from(route('participants.index'))->post(route('participants.store'), [
            'district_id' => null,
            'competition_category_id' => $category->id,
            'participant_role' => 'main',
            'submit_action' => 'submitted',
        ]);

        $response->assertRedirect(route('participants.index'));
        $response->assertSessionHasErrors([
            'name',
            'institution',
            'kk_document',
            'birth_certificate_document',
            'photo_document',
        ]);

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_user_can_save_edit_draft_with_cleared_required_fields(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $category = CompetitionCategory::query()->create([
            'branch' => 'Tilawah',
            'name' => 'Remaja Putra',
            'slug' => 'remaja-putra-'.uniqid(),
            'quota' => 1,
            'age_requirement' => 'Semua usia',
            'sort_order' => 1,
        ]);

        $participant = Participant::query()->create([
            'competition_category_id' => $category->id,
            'registration_number' => 'REG-'.now()->format('ymd').'-EDIT1',
            'participant_role' => 'main',
            'name' => 'Peserta Lama',
            'gender' => 'putra',
            'nik' => '1302062404730007',
            'place_of_birth' => 'Padang',
            'date_of_birth' => '2010-01-01',
            'kk_number' => 'KK-EDIT-001',
            'kk_date' => '2026-01-01',
            'phone' => '081234567890',
            'institution' => 'TPQ Lama',
            'last_education' => 'SMP',
            'current_address' => 'Alamat Lama',
            'ktp_address' => 'Alamat KTP Lama',
            'ktp_district' => 'Kecamatan Lama',
            'ktp_regency' => 'Kabupaten Lama',
            'status' => 'active',
            'verification_status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->post(route('participants.update', ['participant' => $participant->id]), [
            'district_id' => null,
            'competition_category_id' => $category->id,
            'participant_role' => 'main',
            'name' => '',
            'gender' => '',
            'nik' => '',
            'ktp_date' => '',
            'place_of_birth' => '',
            'date_of_birth' => '',
            'kk_number' => '',
            'kk_date' => '',
            'phone' => '',
            'institution' => '',
            'last_education' => '',
            'bank_name' => '',
            'bank_account_number' => '',
            'bank_account_name' => '',
            'current_address' => '',
            'ktp_address' => '',
            'ktp_district' => '',
            'ktp_regency' => '',
            'submit_action' => 'draft',
        ]);

        $response->assertRedirect(route('participants.show', $participant));
        $response->assertSessionHas('status');

        $participant->refresh();

        $this->assertSame('draft', $participant->verification_status);
        $this->assertNull($participant->name);
        $this->assertNull($participant->institution);
    }
}
