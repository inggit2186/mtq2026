<?php

namespace Tests\Feature;

use App\Models\CompetitionCategory;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ParticipantRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_participant_without_last_diploma_document(): void
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
            'name' => 'Peserta Tanpa Ijazah',
            'gender' => 'putra',
            'nik' => '1302062404730003',
            'ktp_date' => null,
            'place_of_birth' => 'Tanah Datar',
            'date_of_birth' => '2010-01-01',
            'kk_number' => 'KK-0001',
            'kk_date' => '2026-01-01',
            'phone' => '081234567890',
            'institution' => 'TPQ Al-Ikhlas',
            'last_education' => 'SMP',
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_name' => null,
            'current_address' => 'Jorong Contoh',
            'ktp_address' => 'Jorong Contoh',
            'ktp_district' => 'Kecamatan Contoh',
            'ktp_regency' => 'Tanah Datar',
            'kk_document' => UploadedFile::fake()->image('kk.jpg', 1200, 1600),
            'ktp_document' => null,
            'birth_certificate_document' => UploadedFile::fake()->image('akta.jpg', 1200, 1600),
            'photo_document' => UploadedFile::fake()->image('foto.jpg', 300, 400),
            'bank_book_document' => null,
            'certificate_documents' => [],
            'other_documents' => [],
            'submit_action' => 'submitted',
        ]);

        $response->assertRedirect(route('participants.index'));
        $response->assertSessionHas('status');

        $participant = Participant::query()
            ->where('name', 'Peserta Tanpa Ijazah')
            ->firstOrFail();

        $this->assertNull($participant->document_last_diploma);
        $this->assertNotNull($participant->document_kk);
        $this->assertNotNull($participant->document_birth_certificate);
        $this->assertNotNull($participant->document_photo);
    }
}
