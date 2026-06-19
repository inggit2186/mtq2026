<?php

namespace App\Http\Controllers;

use App\Models\CompetitionCategory;
use App\Models\Hakim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class HakimController extends Controller
{
    /**
     * Display the hakim management page
     */
    public function index(Request $request): View
    {
        $search = $request->query('search', '');
        $filterCategory = $request->query('category');

        $hakims = Hakim::with('golongans')
            ->search($search)
            ->when($filterCategory, function ($query) use ($filterCategory) {
                $query->whereHas('golongans', function ($q) use ($filterCategory) {
                    $q->where('competition_categories.id', $filterCategory);
                });
            })
            ->orderBy('nama')
            ->get();

        $categoriesForFilter = CompetitionCategory::query()
            ->orderBy('branch')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn ($cat) => [
                'id' => $cat->id,
                'label' => trim(($cat->branch ?? '-').' | '.$cat->name),
                'branch' => $cat->branch,
                'name' => $cat->name,
            ]);

        // Group categories by branch for checkbox display
        $categoriesGrouped = CompetitionCategory::query()
            ->orderBy('branch')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('branch')
            ->map(fn ($cats) => $cats->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
            ])->values()->all())
            ->toArray();

        return view('pages.admin.hakim-management', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel(auth()->user()?->role),
            'navigation' => app(PageController::class)->consoleNavigation((string) auth()->user()?->role, 'admin.hakim'),
            'hakims' => $hakims,
            'categories' => $categoriesForFilter,
            'categoriesGrouped' => $categoriesGrouped,
            'filters' => [
                'search' => $search,
                'category' => $filterCategory,
            ],
        ]);
    }

    /**
     * Store a newly created hakim
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'asal' => ['nullable', 'string', 'max:255'],
            'golongan_ids' => ['nullable', 'array'],
            'golongan_ids.*' => ['integer', 'exists:competition_categories,id'],
        ]);

        $hakim = Hakim::create([
            'nama' => $validated['nama'],
            'asal' => $validated['asal'] ?? null,
        ]);

        if (! empty($validated['golongan_ids'])) {
            $hakim->golongans()->attach($validated['golongan_ids']);
        }

        return redirect()
            ->route('admin.hakim.index')
            ->with('success', 'Hakim berhasil ditambahkan.');
    }

    /**
     * Update the specified hakim
     */
    public function update(Request $request, Hakim $hakim): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'asal' => ['nullable', 'string', 'max:255'],
            'golongan_ids' => ['nullable', 'array'],
            'golongan_ids.*' => ['integer', 'exists:competition_categories,id'],
        ]);

        $hakim->update([
            'nama' => $validated['nama'],
            'asal' => $validated['asal'] ?? null,
        ]);

        // Sync golongan assignments
        $hakim->golongans()->sync($validated['golongan_ids'] ?? []);

        return redirect()
            ->route('admin.hakim.index')
            ->with('success', 'Hakim berhasil diperbarui.');
    }

    /**
     * Remove the specified hakim
     */
    public function destroy(Hakim $hakim): RedirectResponse
    {
        $hakim->golongans()->detach();
        $hakim->delete();

        return redirect()
            ->route('admin.hakim.index')
            ->with('success', 'Hakim berhasil dihapus.');
    }
}
