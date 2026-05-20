<?php

namespace App\Http\Controllers;

use App\Models\CompetitionCategory;
use App\Models\CompetitionLocation;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class CompetitionLocationController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $locations = CompetitionLocation::query()
            ->with('categories')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name')
            ->get();

        return view('pages/competition-locations-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'navigation' => app(PageController::class)->consoleNavigation((string) auth()->user()?->role, 'locations.index'),
            'locations' => $locations,
            'categories' => $categories,
            'locationStats' => [
                'total_locations' => $locations->count(),
                'total_links' => $locations->sum(fn (CompetitionLocation $location): int => $location->categories->count()),
                'locations_with_maps' => $locations->whereNotNull('map_url')->count(),
                'categories_total' => $categories->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $this->validateLocation($request);
        $categoryIds = $this->categoryIds($validated['category_ids'] ?? []);
        $photo = $validated['photo'] ?? null;

        $location = CompetitionLocation::query()->create($this->locationPayload($validated));
        if ($photo instanceof UploadedFile) {
            $this->storeLocationPhotoVariants($location, $photo);
        }
        $location->categories()->sync($categoryIds);

        ActivityLogger::log(
            'location.created',
            (auth()->user()?->name ?? 'Admin').' menambahkan lokasi MTQ "'.$location->label.'".',
            $location,
            [
                'label' => $location->label,
                'venue_name' => $location->venue_name,
                'sort_order' => $location->sort_order,
                'category_ids' => $categoryIds,
            ]
        );

        return redirect()
            ->route('locations.index')
            ->with('status', 'Lokasi MTQ berhasil ditambahkan.');
    }

    public function update(Request $request, CompetitionLocation $competitionLocation): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $this->validateLocation($request);
        $categoryIds = $this->categoryIds($validated['category_ids'] ?? []);
        $photo = $validated['photo'] ?? null;
        $oldPhotoPaths = [
            'photo_path' => $competitionLocation->photo_path,
            'photo_thumb_path' => $competitionLocation->photo_thumb_path,
        ];
        $competitionLocation->fill($this->locationPayload($validated))->save();
        if ($photo instanceof UploadedFile) {
            $this->deleteLocationPhotoFiles($oldPhotoPaths);
            $this->storeLocationPhotoVariants($competitionLocation, $photo);
        }
        $competitionLocation->categories()->sync($categoryIds);

        ActivityLogger::log(
            'location.updated',
            (auth()->user()?->name ?? 'Admin').' memperbarui lokasi MTQ "'.$competitionLocation->label.'".',
            $competitionLocation,
            [
                'label' => $competitionLocation->label,
                'venue_name' => $competitionLocation->venue_name,
                'sort_order' => $competitionLocation->sort_order,
                'category_ids' => $categoryIds,
            ]
        );

        return redirect()
            ->route('locations.index')
            ->with('status', 'Lokasi MTQ berhasil diperbarui.');
    }

    public function destroy(CompetitionLocation $competitionLocation): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $label = $competitionLocation->label;
        $competitionLocation->delete();

        ActivityLogger::log(
            'location.deleted',
            (auth()->user()?->name ?? 'Admin').' menghapus lokasi MTQ "'.$label.'".',
            $competitionLocation,
            ['label' => $label]
        );

        return redirect()
            ->route('locations.index')
            ->with('status', 'Lokasi MTQ "'.$label.'" berhasil dihapus.');
    }

    public function destroyPhoto(CompetitionLocation $competitionLocation): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $label = $competitionLocation->label;
        $this->deleteLocationPhotoFiles([
            'photo_path' => $competitionLocation->photo_path,
            'photo_thumb_path' => $competitionLocation->photo_thumb_path,
        ]);

        $competitionLocation->forceFill([
            'photo_path' => null,
            'photo_thumb_path' => null,
        ])->save();

        ActivityLogger::log(
            'location.photo.deleted',
            (auth()->user()?->name ?? 'Admin').' menghapus foto lokasi MTQ "'.$label.'".',
            $competitionLocation,
            ['label' => $label]
        );

        return redirect()
            ->route('locations.index')
            ->with('status', 'Foto venue untuk "'.$label.'" berhasil dihapus.');
    }

    public function syncFromJson(): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        Artisan::call('locations:sync-mtq');
        $output = trim(Artisan::output());

        return redirect()
            ->route('locations.index')
            ->with('status', $output !== '' ? $output : 'Sinkronisasi lokasi selesai.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLocation(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'venue_name' => ['required', 'string', 'max:255'],
            'map_url' => ['nullable', 'string', 'max:2048'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:99'],
            'category_ids' => ['required', 'array', 'min:1'],
            'category_ids.*' => ['integer', 'exists:competition_categories,id'],
            'photo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function locationPayload(array $validated): array
    {
        return [
            'label' => trim((string) $validated['label']),
            'venue_name' => trim((string) $validated['venue_name']),
            'map_url' => filled($validated['map_url'] ?? null) ? trim((string) $validated['map_url']) : null,
            'sort_order' => (int) $validated['sort_order'],
        ];
    }

    /**
     * @param  array<int|string, mixed>  $input
     * @return list<int>
     */
    private function categoryIds(array $input): array
    {
        return collect($input)
            ->filter(fn ($value): bool => filled($value))
            ->map(fn ($value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function storeLocationPhotoVariants(CompetitionLocation $location, UploadedFile $photo): array
    {
        $sourcePath = $photo->getRealPath();

        if ($sourcePath === false || ! is_file($sourcePath)) {
            throw new \RuntimeException('File lokasi tidak valid.');
        }

        $binary = file_get_contents($sourcePath);
        if ($binary === false) {
            throw new \RuntimeException('File lokasi tidak bisa dibaca.');
        }

        $image = imagecreatefromstring($binary);
        if ($image === false) {
            throw new \RuntimeException('File lokasi tidak bisa diproses.');
        }

        $main = $this->encodeLocationImage($image, 1600, 1600, 82);
        $thumb = $this->encodeLocationImage($image, 640, 640, 76);
        imagedestroy($image);

        $directory = 'images/lokasi-mtq-custom/'.$location->id;
        $mainPath = $directory.'/main.'.$main['extension'];
        $thumbPath = $directory.'/thumb.'.$thumb['extension'];

        $this->ensurePublicDirectory(dirname(public_path($mainPath)));
        $this->ensurePublicDirectory(dirname(public_path($thumbPath)));

        file_put_contents(public_path($mainPath), $main['contents']);
        file_put_contents(public_path($thumbPath), $thumb['contents']);

        $location->forceFill([
            'photo_path' => $mainPath,
            'photo_thumb_path' => $thumbPath,
        ])->save();

        return [
            'photo_path' => $mainPath,
            'photo_thumb_path' => $thumbPath,
        ];
    }

    private function encodeLocationImage($sourceImage, int $maxWidth, int $maxHeight, int $quality): array
    {
        $width = imagesx($sourceImage);
        $height = imagesy($sourceImage);
        $ratio = min($maxWidth / max(1, $width), $maxHeight / max(1, $height), 1);
        $targetWidth = max(1, (int) round($width * $ratio));
        $targetHeight = max(1, (int) round($height * $ratio));
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $supportsWebp = function_exists('imagewebp');
        $extension = $supportsWebp ? 'webp' : 'jpg';

        if ($supportsWebp) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
        } else {
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $white);
        }

        imagecopyresampled($canvas, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        if ($supportsWebp) {
            imagewebp($canvas, null, $quality);
        } else {
            imagejpeg($canvas, null, $quality);
        }
        $contents = ob_get_clean();
        imagedestroy($canvas);

        if ($contents === false || $contents === '') {
            throw new \RuntimeException('Foto lokasi gagal dikompres.');
        }

        return [
            'extension' => $extension,
            'contents' => $contents,
        ];
    }

    private function deleteLocationPhotoFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $trimmed = trim((string) $path);
            if ($trimmed === '') {
                continue;
            }

            $absolutePath = public_path($trimmed);
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }

    private function ensurePublicDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            File::makeDirectory($directory, 0755, true, true);
        }
    }
}
