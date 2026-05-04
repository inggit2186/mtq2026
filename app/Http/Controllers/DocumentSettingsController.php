<?php

namespace App\Http\Controllers;

use App\Models\CompetitionCategory;
use App\Models\DocumentSetting;
use App\Models\Participant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DocumentSettingsController extends Controller
{
    public function index(): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        return view('pages/document-settings-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'documentConfig' => app(PageController::class)->documentConfig(),
            'documentSettingsReady' => Schema::hasTable('document_settings'),
            'documentPreview' => $this->documentPreviewContext(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        if (! Schema::hasTable('document_settings')) {
            return redirect()
                ->route('admin.documents')
                ->with('status', 'Tabel document_settings belum tersedia. Jalankan migrate terlebih dahulu agar metadata bisa disimpan.');
        }

        $validated = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'event_title' => ['required', 'string', 'max:255'],
            'event_location' => ['nullable', 'string', 'max:255'],
            'signature_city' => ['required', 'string', 'max:255'],
            'officials' => ['required', 'array'],
            'officials.chief_judge.title' => ['required', 'string', 'max:255'],
            'officials.chief_judge.name' => ['required', 'string', 'max:255'],
            'officials.secretary.title' => ['required', 'string', 'max:255'],
            'officials.secretary.name' => ['required', 'string', 'max:255'],
            'officials.committee_coordinator.title' => ['required', 'string', 'max:255'],
            'officials.committee_coordinator.name' => ['required', 'string', 'max:255'],
            'officials.committee_chair.title' => ['required', 'string', 'max:255'],
            'officials.committee_chair.name' => ['required', 'string', 'max:255'],
        ]);

        $setting = DocumentSetting::current() ?? new DocumentSetting();
        $setting->fill($validated);
        $setting->save();

        return redirect()
            ->route('admin.documents')
            ->with('status', 'Metadata dokumen resmi berhasil diperbarui.');
    }

    protected function documentPreviewContext(): array
    {
        $participants = Participant::query()
            ->with('category')
            ->where('verification_status', 'verified')
            ->orderBy('name')
            ->get();

        $categories = CompetitionCategory::query()
            ->orderBy('sort_order')
            ->orderBy('branch')
            ->orderBy('name')
            ->get();

        $selectedParticipant = $participants->first();

        return [
            'participants' => $participants
                ->map(fn (Participant $participant): array => [
                    'id' => $participant->id,
                    'label' => trim($participant->name.' - '.($participant->registration_number ?? 'Tanpa nomor')),
                    'category' => trim(($participant->category?->branch ?? '-').' / '.($participant->category?->name ?? '-')),
                ])
                ->values()
                ->all(),
            'categories' => $categories
                ->map(fn (CompetitionCategory $category): array => [
                    'id' => $category->id,
                    'label' => trim(($category->branch ?? '-').' - '.($category->name ?? '-')),
                ])
                ->values()
                ->all(),
            'branches' => $categories
                ->pluck('branch')
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'selectedParticipantId' => $selectedParticipant?->id,
            'urls' => [
                'participantPreview' => route('results.print', array_filter([
                    'preview' => 1,
                    'participant_id' => $selectedParticipant?->id,
                ])),
                'participantPrint' => route('results.print', array_filter([
                    'participant_id' => $selectedParticipant?->id,
                ])),
                'recapPreview' => route('results.recap-print', ['preview' => 1]),
                'recapPrint' => route('results.recap-print'),
            ],
        ];
    }
}
