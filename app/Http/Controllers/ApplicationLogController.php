<?php

namespace App\Http\Controllers;

use App\Models\ApplicationLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ApplicationLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(in_array(auth()->user()?->role, ['admin', 'panitia'], true), 403);

        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:255'],
            'action' => ['nullable', 'string', 'max:120'],
            'user_role' => ['nullable', 'string', 'max:40'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        if (! Schema::hasTable('application_logs')) {
            return view('pages/application-logs-v2', [
                'assets' => app(PageController::class)->viteAssets(),
                'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
                'logs' => collect(),
                'filters' => $filters,
                'actions' => collect(),
                'logStats' => [
                    'total' => 0,
                    'today' => 0,
                    'participant' => 0,
                    'lot_maqra' => 0,
                ],
            ]);
        }

        $logsQuery = ApplicationLog::query()
            ->with('actor')
            ->latest()
            ->when(filled($filters['action'] ?? null), fn ($query) => $query->where('action', $filters['action']))
            ->when(filled($filters['user_role'] ?? null), fn ($query) => $query->where('user_role', $filters['user_role']))
            ->when(filled($filters['date_from'] ?? null), fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when(filled($filters['date_to'] ?? null), fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->when(filled($filters['keyword'] ?? null), function ($query) use ($filters): void {
                $keyword = trim((string) $filters['keyword']);

                $query->where(function ($subQuery) use ($keyword): void {
                    $subQuery
                        ->where('description', 'like', '%'.$keyword.'%')
                        ->orWhere('user_name', 'like', '%'.$keyword.'%')
                        ->orWhere('subject_name', 'like', '%'.$keyword.'%')
                        ->orWhere('ip_address', 'like', '%'.$keyword.'%');
                });
            });

        return view('pages/application-logs-v2', [
            'assets' => app(PageController::class)->viteAssets(),
            'rolePanel' => app(PageController::class)->rolePanel((string) auth()->user()?->role),
            'logs' => $logsQuery->paginate(25)->withQueryString(),
            'filters' => $filters,
            'actions' => ApplicationLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'logStats' => [
                'total' => ApplicationLog::query()->count(),
                'today' => ApplicationLog::query()->whereDate('created_at', today())->count(),
                'participant' => ApplicationLog::query()->where('action', 'like', 'participant.%')->count(),
                'lot_maqra' => ApplicationLog::query()
                    ->where(function ($query): void {
                        $query
                            ->where('action', 'like', 'participant.lot.%')
                            ->orWhere('action', 'like', 'participant.maqra.%');
                    })
                    ->count(),
            ],
        ]);
    }
}
