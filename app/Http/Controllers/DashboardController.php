<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ServiceRequest;
use App\Models\ServiceType;
use App\Models\User;
use App\Services\WorkflowService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user      = auth()->user();
        $isClient  = !$user->hasPermission('transition_stage')
                  && !$user->hasPermission('update_status')
                  && !$user->hasPermission('manage_users');

        // ── Stats ────────────────────────────────────────────────────
        $baseQuery = $isClient
            ? ServiceRequest::where('user_id', $user->id)
            : ServiceRequest::query();

        $total    = (clone $baseQuery)->count();
        $newCount = (clone $baseQuery)->where('current_stage', 1)->count();
        $active   = (clone $baseQuery)->whereBetween('current_stage', [2, 6])
                                      ->where('is_rejected', false)->count();
        $closed   = (clone $baseQuery)->where(function ($q) {
                        $q->where('stage_status', 'Closed')->orWhere('is_rejected', true);
                    })->count();
        $rejected = (clone $baseQuery)->where('is_rejected', true)->count();

        // ── Stage breakdown ──────────────────────────────────────────
        $byStage = (clone $baseQuery)
            ->select('current_stage', DB::raw('count(*) as count'))
            ->where('is_rejected', false)
            ->groupBy('current_stage')
            ->pluck('count', 'current_stage');

        // ── Overdue (stuck > 5 days in same stage) ───────────────────
        $overdue = (clone $baseQuery)
            ->where('is_rejected', false)
            ->where('stage_status', '!=', 'Closed')
            ->where('stage_entered_at', '<', now()->subDays(5))
            ->with(['user', 'assignedTo'])
            ->orderBy('stage_entered_at')
            ->limit(5)
            ->get();

        // ── My assigned requests (employees) ────────────────────────
        $myRequests = $isClient
            ? collect()
            : ServiceRequest::where('assigned_to', $user->id)
                ->where('is_rejected', false)
                ->where('stage_status', '!=', 'Closed')
                ->with(['user'])
                ->orderBy('stage_entered_at')
                ->limit(8)
                ->get();

        // ── Client: own recent requests ──────────────────────────────
        $clientRequests = $isClient
            ? ServiceRequest::where('user_id', $user->id)
                ->with(['assignedTo'])
                ->orderByDesc('updated_at')
                ->limit(8)
                ->get()
            : collect();

        // ── Recent activity ──────────────────────────────────────────
        $recentActivity = ActivityLog::with([])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $actorIds = $recentActivity->pluck('user')->filter()->unique();
        $actors   = User::whereIn('id', $actorIds)->pluck('name', 'id');

        // ── New requests today (admin/employee) ──────────────────────
        $todayCount = $isClient ? 0
            : ServiceRequest::whereDate('created_at', today())->count();

        // ── Chart: requests per month (last 6 months) ────────────────
        $monthlyLabels = [];
        $monthlyData   = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthlyLabels[] = $month->format('M Y');
            $monthlyData[]   = (clone $baseQuery)
                ->whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        // ── Chart: by status ─────────────────────────────────────────
        $statusCounts = [];
        foreach (['New', 'Under Review', 'Approved', 'Rejected', 'Completed'] as $s) {
            $statusCounts[$s] = (clone $baseQuery)->where('status', $s)->count();
        }

        // ── Avg resolution time (closed requests) ────────────────────
        $avgDays = null;
        if (!$isClient) {
            $avgSeconds = ServiceRequest::where('stage_status', 'Closed')
                ->whereNotNull('stage_entered_at')
                ->selectRaw('AVG(DATEDIFF(stage_entered_at, created_at)) as avg_days')
                ->value('avg_days');
            $avgDays = $avgSeconds ? round($avgSeconds, 1) : null;
        }

        // ── By service type ──────────────────────────────────────────
        $byServiceType = (clone $baseQuery)
            ->select('service_type_id', DB::raw('count(*) as total'))
            ->with('serviceType')
            ->groupBy('service_type_id')
            ->get()
            ->map(fn($r) => [
                'name'  => $r->serviceType->name ?? __('Unknown'),
                'total' => $r->total,
            ])
            ->sortByDesc('total')
            ->values();

        // ── Latest 5 requests ────────────────────────────────────────
        $latestRequests = (clone $baseQuery)
            ->with(['user', 'serviceType'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'user', 'isClient',
            'total', 'newCount', 'active', 'closed', 'rejected',
            'byStage', 'overdue',
            'myRequests', 'clientRequests',
            'recentActivity', 'actors',
            'todayCount',
            'monthlyLabels', 'monthlyData',
            'statusCounts', 'avgDays',
            'byServiceType', 'latestRequests',
        ));
    }
}
