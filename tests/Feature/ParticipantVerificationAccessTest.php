<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\District;
use App\Models\OfficialAccessSetting;
use App\Models\Participant;
use App\Models\ParticipantVerificationLog;
use App\Models\User;
use App\Models\UserDistrictAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_panitia_can_verify_participant_without_approving_mandate_first(): void
    {
        [$participant, $panitia] = $this->createParticipantAndPanitia('submitted');

        OfficialAccessSetting::query()->create([
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
            'participant_verification_open' => true,
        ]);

        $this->actingAs($panitia)
            ->post(route('participants.verify', ['participant' => $participant, 'page' => 3]), [
                'verification_status' => 'verified',
                'verification_notes' => 'Lengkap',
            ])
            ->assertRedirect(route('participants.list', ['page' => 3]));

        $participant->refresh();

        $this->assertSame('verified', $participant->verification_status);
    }

    public function test_panitia_can_see_district_mandate_on_participant_detail(): void
    {
        [$participant, $panitia] = $this->createParticipantAndPanitia();

        OfficialAccessSetting::query()->create([
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
            'participant_verification_open' => true,
        ]);

        $response = $this->actingAs($panitia)->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('Mandat Kecamatan');
        $response->assertSee('Surat Mandat Kecamatan');
        $response->assertSee('Pratinjau PDF');
    }

    public function test_official_does_not_see_verifier_name_in_verification_history(): void
    {
        [$participant] = $this->createParticipantAndPanitia();

        $official = User::factory()->create([
            'role' => 'official',
            'district_id' => $participant->district_id,
        ]);

        ParticipantVerificationLog::query()->create([
            'participant_id' => $participant->id,
            'verified_by' => $official->id,
            'status' => 'verified',
            'notes' => 'Sudah lengkap',
        ]);

        $response = $this->actingAs($official)->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('Riwayat Verifikasi');
        $response->assertDontSee($official->name);
    }

    public function test_export_pdf_uses_simplified_columns(): void
    {
        [$participant, $panitia] = $this->createParticipantAndPanitia();
        Storage::disk('public')->put('participants/documents/photo/test-photo.jpg', 'fake image');
        $participant->update([
            'verification_status' => 'verified',
            'verification_notes' => 'Terverifikasi untuk kebutuhan uji.',
            'document_photo' => 'participants/documents/photo/test-photo.jpg',
        ]);

        OfficialAccessSetting::query()->create([
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
            'participant_verification_open' => true,
        ]);

        $response = $this->actingAs($panitia)->get(route('participants.export.pdf'));

        $response->assertOk();
        $response->assertSee('Rekap Data Peserta');
        $response->assertSee('Verifikasi');
        $response->assertSee('Foto');
        $response->assertSee('MS');
        $response->assertSee('TMS');
        $response->assertSee('Memenuhi Syarat');
        $response->assertSee('Tidak Memenuhi Syarat');
        $response->assertSee('Umur per 1 Juli');
        $response->assertSee('✓');
        $response->assertSee('photo-thumb');
        $response->assertSee($participant->name);
        $response->assertSee($participant->district->name);
        $response->assertDontSee('No. HP');
        $response->assertDontSee('Dokumen');
        $response->assertDontSee('Catatan Verifikasi');
    }

    public function test_export_verification_excel_uses_pdf_style_columns(): void
    {
        [$participant, $panitia] = $this->createParticipantAndPanitia();
        Storage::disk('public')->put('participants/documents/photo/test-photo.jpg', 'fake image');
        $participant->update([
            'verification_status' => 'rejected',
            'verification_notes' => 'Perlu perbaikan.',
            'document_photo' => 'participants/documents/photo/test-photo.jpg',
        ]);

        OfficialAccessSetting::query()->create([
            'participant_registration_open' => true,
            'participant_edit_open' => true,
            'mandate_upload_open' => true,
            'participant_documents_open' => true,
            'participant_verification_open' => true,
        ]);

        $response = $this->actingAs($panitia)->get(route('participants.export.verification.excel'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
        $response->assertSee('Export Data Verifikasi');
        $response->assertSee('Foto');
        $response->assertSee('Verifikasi');
        $response->assertSee('MS');
        $response->assertSee('TMS');
        $response->assertSee('TMS');
        $response->assertSee('✓');
        $response->assertSee($participant->name);
    }

    /**
     * @return array{0: \App\Models\Participant, 1: \App\Models\User}
     */
    private function createParticipantAndPanitia(string $mandateStatus = 'verified'): array
    {
        $district = District::query()->create([
            'name' => 'Kecamatan Pariangan',
            'slug' => 'pariangan',
            'mandate_document_path' => 'districts/mandates/pariangan.pdf',
            'mandate_uploaded_at' => now(),
            'mandate_status' => $mandateStatus,
            'mandate_verification_notes' => $mandateStatus === 'verified'
                ? 'Sudah diverifikasi untuk kebutuhan uji.'
                : 'Menunggu verifikasi untuk kebutuhan uji.',
            'mandate_verified_at' => $mandateStatus === 'verified' ? now() : null,
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
