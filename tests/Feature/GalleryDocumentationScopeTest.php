<?php

namespace Tests\Feature;

use App\Models\ActivityDocumentation;
use App\Models\District;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryDocumentationScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_official_only_sees_gallery_items_from_own_district(): void
    {
        $districtA = District::query()->create([
            'name' => 'Kecamatan Pariangan',
            'slug' => 'pariangan',
        ]);

        $districtB = District::query()->create([
            'name' => 'Kecamatan Rambatan',
            'slug' => 'rambatan',
        ]);

        $official = User::factory()->create([
            'role' => 'official',
            'district_id' => $districtA->id,
        ]);

        ActivityDocumentation::query()->create([
            'caption' => 'Foto Kecamatan Pariangan',
            'image_path' => 'gallery/pariangan.jpg',
            'thumbnail_path' => 'gallery/pariangan-thumb.jpg',
            'uploaded_by' => $official->id,
            'district_id' => $districtA->id,
            'is_active' => true,
            'is_cover_homepage' => false,
            'sort_order' => 1,
        ]);

        ActivityDocumentation::query()->create([
            'caption' => 'Foto Kecamatan Rambatan',
            'image_path' => 'gallery/rambatan.jpg',
            'thumbnail_path' => 'gallery/rambatan-thumb.jpg',
            'uploaded_by' => User::factory()->create([
                'role' => 'official',
                'district_id' => $districtB->id,
            ])->id,
            'district_id' => $districtB->id,
            'is_active' => true,
            'is_cover_homepage' => false,
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($official)->get(route('gallery.index'));

        $response->assertOk();
        $response->assertSee('Foto Kecamatan Pariangan');
        $response->assertDontSee('Foto Kecamatan Rambatan');
    }
}
