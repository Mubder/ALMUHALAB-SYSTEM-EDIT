@extends('layouts.app')
@section('title', __('Dashboard'))

@php
    use App\Services\WorkflowService;

    $actionLabels = [
        'created'                  => ['bi-plus-circle',          'text-success',  'Created'],
        'updated'                  => ['bi-pencil',               'text-primary',  'Updated'],
        'deleted'                  => ['bi-trash',                'text-danger',   'Deleted'],
        'stage_advanced'           => ['bi-arrow-right-circle',   'text-success',  'Stage Advanced'],
        'stage_returned'           => ['bi-arrow-left-circle',    'text-warning',  'Stage Returned'],
        'stage_force_transitioned' => ['bi-skip-forward-circle',  'text-danger',   'Force Moved'],
        'stage_status_changed'     => ['bi-arrow-repeat',         'text-primary',  'Status Changed'],
        'request_rejected'         => ['bi-x-circle',             'text-danger',   'Rejected'],
        'follow_up_created'        => ['bi-plus-circle-dotted',   'text-success',  'Step Added'],
        'follow_up_completed'      => ['bi-check-circle',         'text-success',  'Step Completed'],
        'follow_up_deleted'        => ['bi-dash-circle',          'text-danger',   'Step Removed'],
        'service_added'            => ['bi-grid-plus',            'text-success',  'Service Added'],
        'service_updated'          => ['bi-grid',                 'text-primary',  'Service Updated'],
        'service_removed'          => ['bi-grid-x',               'text-danger',   'Service Removed'],
        'attachment_uploaded'      => ['bi-paperclip',            'text-success',  'File Uploaded'],
        'attachment_deleted'       => ['bi-paperclip',            'text-danger',   'File Deleted'],
    ];
@endphp

@push('styles')
<style>
.stat-card {
    border-radius: var(--radius-lg);
    padding: 1.25rem 1.5rem;
    border: 1px solid transparent;
    transition: transform .15s, box-shadow .15s, border-color .15s;
    cursor: pointer;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); border-color: rgba(0,0,0,.08); }
.stat-card .stat-value { font-size: 2rem; font-weight: 700; line-height: 1; }
.stat-card .stat-label { font-size: .78rem; font-weight: 500; opacity: .75; margin-top: .25rem; text-transform: uppercase; letter-spacing: .05em; }
.stat-card .stat-icon  { font-size: 2rem; opacity: .2; }

.stage-bar-wrap { height: 6px; background: var(--bs-gray-200); border-radius: 99px; overflow: hidden; }

.hover-stage-card { transition: transform .15s, box-shadow .15s, border-color .15s; cursor: pointer; }
.hover-stage-card:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.04); border-color: var(--primary) !important; }

.hover-service-row { display: block; text-decoration: none; color: inherit; cursor: pointer; }
.hover-service-row .service-item-wrap { padding: .25rem .5rem; border-radius: 6px; transition: background-color .15s, transform .15s; }
.hover-service-row:hover .service-item-wrap { background-color: var(--bs-gray-100); transform: translateX(2px); }
.stage-bar      { height: 100%; border-radius: 99px; transition: width .5s ease; }

.overdue-badge { animation: pulse-red 1.8s infinite; }
@keyframes pulse-red { 0%, 100% { opacity: 1; } 50% { opacity: .5; } }

.request-row { transition: background .1s; }
.request-row:hover { background: var(--bs-primary-bg-subtle); }

.chart-card { background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--color-border); padding: 1.25rem 1.5rem; }
.chart-card h6 { font-size: .72rem; letter-spacing: .07em; text-transform: uppercase; color: var(--color-muted); font-weight: 700; margin-bottom: 1rem; }
</style>
@endpush

@section('content')

{{-- ── Greeting ─────────────────────────────────────────────── --}}
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0">
            @php
                $hour = now()->hour;
                $greeting = $hour < 12 ? __('Good morning') : ($hour < 17 ? __('Good afternoon') : __('Good evening'));
            @endphp
            {{ $greeting }}, {{ explode(' ', $user->name)[0] }} 👋
        </h1>
        <p class="text-muted small mb-0">{{ now()->format('l, d F Y') }}</p>
    </div>
    @if(!$isClient && $todayCount > 0)
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2" style="font-size:.8rem">
            <i class="bi bi-plus-circle me-1"></i>{{ $todayCount }} {{ $todayCount > 1 ? __('new requests today') : __('new request today') }}
        </span>
    @elseif($isClient)
        <a href="{{ route('service-requests.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>{{ __('New Request') }}
        </a>
    @endif
</div>

{{-- ── Overdue Alert ────────────────────────────────────────── --}}
@if(!$isClient && $overdue->count())
<div class="alert alert-warning d-flex align-items-start gap-3 mb-4 border-0 shadow-sm" style="border-radius:var(--radius-lg)">
    <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0 mt-1 overdue-badge"></i>
    <div class="flex-grow-1">
        <div class="fw-bold mb-1">{{ $overdue->count() }} {{ $overdue->count() > 1 ? __('requests') : __('request') }} {{ __('stuck for over 5 days') }}</div>
        <div class="d-flex flex-wrap gap-2">
            @foreach($overdue as $sr)
            <a href="{{ route('service-requests.show', $sr) }}"
               class="badge bg-warning text-dark text-decoration-none fw-normal" style="font-size:.75rem">
                #{{ $sr->id }} {{ Str::limit($sr->title, 25) }}
                <span class="ms-1 opacity-75">{{ $sr->stage_entered_at->diffForHumans() }}</span>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── Stat Cards ───────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl">
        <a href="{{ route('service-requests.index') }}" class="text-decoration-none d-block">
            <div class="stat-card bg-white border h-100" style="border-left:4px solid #2563eb !important">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value text-primary">{{ $total }}</div>
                        <div class="stat-label text-muted">{{ __('Total Requests') }}</div>
                    </div>
                    <span class="rounded-3 p-2 bg-primary-subtle text-primary" style="font-size:1.3rem"><i class="bi bi-folder2-open"></i></span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl">
        <a href="{{ route('service-requests.index', ['stage' => 1]) }}" class="text-decoration-none d-block">
            <div class="stat-card bg-white border h-100" style="border-left:4px solid #0891b2 !important">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value text-info">{{ $newCount }}</div>
                        <div class="stat-label text-muted">{{ __('New / Inbox') }}</div>
                    </div>
                    <span class="rounded-3 p-2 bg-info-subtle text-info" style="font-size:1.3rem"><i class="bi bi-inbox"></i></span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl">
        <a href="{{ route('service-requests.index', ['active' => 1]) }}" class="text-decoration-none d-block">
            <div class="stat-card bg-white border h-100" style="border-left:4px solid #d97706 !important">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value text-warning">{{ $active }}</div>
                        <div class="stat-label text-muted">{{ __('In Progress') }}</div>
                    </div>
                    <span class="rounded-3 p-2 bg-warning-subtle text-warning" style="font-size:1.3rem"><i class="bi bi-activity"></i></span>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl">
        <a href="{{ route('service-requests.index', ['closed' => 1]) }}" class="text-decoration-none d-block">
            <div class="stat-card bg-white border h-100" style="border-left:4px solid #16a34a !important">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value text-success">{{ $closed }}</div>
                        <div class="stat-label text-muted">{{ __('Closed') }}</div>
                    </div>
                    <span class="rounded-3 p-2 bg-success-subtle text-success" style="font-size:1.3rem"><i class="bi bi-check-circle"></i></span>
                </div>
            </div>
        </a>
    </div>
    @if(!$isClient)
    <div class="col-6 col-xl">
        <a href="{{ route('service-requests.index', ['rejected' => 1]) }}" class="text-decoration-none d-block">
            <div class="stat-card bg-white border h-100" style="border-left:4px solid #dc2626 !important">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value text-danger">{{ $rejected }}</div>
                        <div class="stat-label text-muted">{{ __('Rejected') }}</div>
                    </div>
                    <span class="rounded-3 p-2 bg-danger-subtle text-danger" style="font-size:1.3rem"><i class="bi bi-x-circle"></i></span>
                </div>
            </div>
        </a>
    </div>
    @if($avgDays !== null)
    <div class="col-6 col-xl">
        <a href="{{ route('service-requests.index', ['closed' => 1]) }}" class="text-decoration-none d-block">
            <div class="stat-card bg-white border h-100" style="border-left:4px solid #7c3aed !important">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="stat-value" style="color:#7c3aed">{{ $avgDays }}</div>
                        <div class="stat-label text-muted">{{ __('Avg. Days to Close') }}</div>
                    </div>
                    <span class="rounded-3 p-2" style="background:rgba(124,58,237,.1);color:#7c3aed;font-size:1.3rem"><i class="bi bi-stopwatch"></i></span>
                </div>
            </div>
        </a>
    </div>
    @endif
    @endif
</div>

@if(!$isClient && $total > 0)
{{-- ── Row 1: Charts ─────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-7">
        <div class="chart-card h-100">
            <h6><i class="bi bi-bar-chart-fill me-1 text-primary"></i>{{ __('Requests per Month') }}</h6>
            <canvas id="chartMonthly" height="100"></canvas>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="chart-card h-100">
            <h6><i class="bi bi-pie-chart-fill me-1 text-info"></i>{{ __('By Stage') }}</h6>
            <canvas id="chartStages" height="120"></canvas>
        </div>
    </div>
</div>

{{-- ── Row 2: Stage pipeline + Service type breakdown ────────── --}}
<div class="row g-3 mb-4">

    {{-- Stage pipeline cards --}}
    <div class="col-lg-8">
        <div class="chart-card h-100">
            <h6><i class="bi bi-bar-chart-steps me-1 text-primary"></i>{{ __('Requests by Stage') }}</h6>
            <div class="row g-2">
                @foreach(WorkflowService::STAGES as $n => $cfg)
                    @php
                        $cnt = $byStage[$n] ?? 0;
                        $pct = $total > 0 ? round($cnt / $total * 100) : 0;
                    @endphp
                    <div class="col-6">
                        <a href="{{ route('service-requests.index', ['stage' => $n]) }}" class="text-decoration-none text-dark d-block">
                            <div class="d-flex align-items-center gap-2 p-2 rounded-2 border bg-white hover-stage-card">
                                <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 text-{{ $cfg['color'] }} bg-{{ $cfg['color'] }}-subtle"
                                      style="width:32px;height:32px;font-size:.85rem">
                                    <i class="bi {{ $cfg['icon'] }}"></i>
                                </span>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="small fw-600 text-truncate" style="font-size:.73rem">{{ __($cfg['label']) }}</span>
                                        <span class="fw-700 small text-{{ $cfg['color'] }}">{{ $cnt }}</span>
                                    </div>
                                    <div class="stage-bar-wrap">
                                        <div class="stage-bar bg-{{ $cfg['color'] }}" style="width:{{ $pct }}%"></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Service type breakdown --}}
    <div class="col-lg-4">
        <div class="chart-card h-100">
            <h6><i class="bi bi-grid-fill me-1 text-warning"></i>{{ __('By Service Type') }}</h6>
            @if($byServiceType->isEmpty())
                <div class="text-center text-muted py-4 small">{{ __('No data yet') }}</div>
            @else
                <div class="d-flex flex-column gap-2">
                    @foreach($byServiceType->take(7) as $st)
                    @php $stPct = $total > 0 ? round($st['total'] / $total * 100) : 0; @endphp
                    <a href="{{ route('service-requests.index', ['service_type_id' => $st['id']]) }}" class="hover-service-row">
                        <div class="service-item-wrap">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-truncate me-2" style="font-size:.75rem;max-width:65%">{{ $st['name'] }}</span>
                                <span class="fw-700 small text-primary">{{ $st['total'] }}</span>
                            </div>
                            <div class="stage-bar-wrap">
                                <div class="stage-bar bg-warning" style="width:{{ $stPct }}%"></div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ── Row 3: Latest requests ──────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="chart-card h-100">
            <h6><i class="bi bi-clock-history me-1 text-secondary"></i>{{ __('Latest Requests') }}</h6>
            @if($latestRequests->isEmpty())
                <div class="text-center text-muted py-4 small">{{ __('No requests yet') }}</div>
            @else
            <div class="d-flex flex-column gap-1">
                @foreach($latestRequests as $sr)
                @php $sCfg = WorkflowService::stage($sr->current_stage); @endphp
                <a href="{{ route('service-requests.show', $sr) }}"
                   class="request-row d-flex align-items-center gap-2 p-2 rounded-2 text-decoration-none text-dark">
                    <span class="badge bg-{{ $sCfg['color'] }}-subtle text-{{ $sCfg['color'] }} border flex-shrink-0"
                          style="font-size:.62rem;min-width:5rem;text-align:center">
                        <i class="bi {{ $sCfg['icon'] }} me-1"></i>{{ __($sCfg['label']) }}
                    </span>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="small fw-500 text-truncate">{{ $sr->title }}</div>
                        <div class="text-muted" style="font-size:.68rem">
                            {{ $sr->serviceType->name ?? '—' }}
                            @if(!$isClient) · {{ $sr->user->name ?? '—' }} @endif
                        </div>
                    </div>
                    <span class="text-muted flex-shrink-0" style="font-size:.68rem">{{ $sr->created_at->diffForHumans() }}</span>
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>
@endif

{{-- ── Main Two-Column ──────────────────────────────────────── --}}
<div class="row g-4">

    {{-- LEFT ──────────────────────────────────────────────── --}}
    <div class="col-lg-7">

        @if(!$isClient)
        {{-- My Assigned Requests --}}
        <div class="page-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                    <i class="bi bi-person-check me-1"></i>{{ __('Assigned to Me') }}
                </h6>
                <a href="{{ route('service-requests.index') }}" class="btn btn-outline-secondary btn-sm" style="font-size:.75rem">{{ __('View All') }}</a>
            </div>
            @if($myRequests->isEmpty())
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-inbox d-block mb-2" style="font-size:1.5rem;opacity:.3"></i>
                    {{ __('No requests assigned to you.') }}
                </div>
            @else
                <div class="d-flex flex-column gap-1">
                    @foreach($myRequests as $sr)
                        @php $cfg = WorkflowService::stage($sr->current_stage); @endphp
                        <a href="{{ route('service-requests.show', $sr) }}"
                           class="request-row d-flex align-items-center gap-3 p-2 rounded-2 text-decoration-none text-dark">
                            <span class="badge bg-{{ $cfg['color'] }}-subtle text-{{ $cfg['color'] }} border border-{{ $cfg['color'] }}-subtle flex-shrink-0"
                                  style="font-size:.65rem;min-width:5rem;text-align:center">
                                <i class="bi {{ $cfg['icon'] }} me-1"></i>{{ $cfg['label'] }}
                            </span>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="small fw-500 text-truncate">{{ $sr->title }}</div>
                                <div class="text-muted" style="font-size:.7rem">{{ $sr->user->name ?? '—' }} · {{ $sr->stage_status }}</div>
                            </div>
                            <div class="text-muted flex-shrink-0" style="font-size:.7rem">
                                {{ $sr->stage_entered_at?->diffForHumans() ?? $sr->updated_at->diffForHumans() }}
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- New Inbox --}}
        @php
            $newRequests = \App\Models\ServiceRequest::where('current_stage', 1)
                ->with('user')->orderByDesc('created_at')->limit(5)->get();
        @endphp
        @if($newRequests->count())
        <div class="page-card">
            <h6 class="fw-bold mb-3 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                <i class="bi bi-inbox-fill me-1 text-info"></i>{{ __('New Inbox') }}
            </h6>
            <div class="d-flex flex-column gap-1">
                @foreach($newRequests as $sr)
                <a href="{{ route('service-requests.show', $sr) }}"
                   class="request-row d-flex align-items-center gap-3 p-2 rounded-2 text-decoration-none text-dark">
                    <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                         style="width:1.8rem;height:1.8rem;font-size:.7rem">
                        {{ strtoupper(substr($sr->user->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="small fw-500 text-truncate">{{ $sr->title }}</div>
                        <div class="text-muted" style="font-size:.7rem">{{ $sr->user->name ?? '—' }}</div>
                    </div>
                    <div class="text-muted flex-shrink-0" style="font-size:.7rem">{{ $sr->created_at->diffForHumans() }}</div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @else
        {{-- Client: My Requests --}}
        <div class="page-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                    <i class="bi bi-folder2-open me-1"></i>{{ __('My Requests') }}
                </h6>
                <a href="{{ route('service-requests.index') }}" class="btn btn-outline-secondary btn-sm" style="font-size:.75rem">{{ __('View All') }}</a>
            </div>
            @if($clientRequests->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-folder2-open d-block mb-2" style="font-size:2rem;opacity:.3"></i>
                    <div class="small mb-3">{{ __('You have no requests yet.') }}</div>
                    <a href="{{ route('service-requests.create') }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-1"></i>{{ __('Submit First Request') }}
                    </a>
                </div>
            @else
                <div class="d-flex flex-column gap-2">
                    @foreach($clientRequests as $sr)
                        @php $cfg = WorkflowService::stage($sr->current_stage); @endphp
                        <a href="{{ route('service-requests.show', $sr) }}"
                           class="request-row d-flex align-items-center gap-3 p-2 rounded-2 text-decoration-none text-dark">
                            <span class="badge bg-{{ $cfg['color'] }}-subtle text-{{ $cfg['color'] }} border border-{{ $cfg['color'] }}-subtle flex-shrink-0"
                                  style="font-size:.65rem;min-width:5.5rem;text-align:center">
                                <i class="bi {{ $cfg['icon'] }} me-1"></i>{{ $cfg['label'] }}
                            </span>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="small fw-500 text-truncate">{{ $sr->title }}</div>
                                <div class="text-muted" style="font-size:.7rem">
                                    {{ $sr->stage_status }}
                                    @if($sr->assignedTo) · {{ $sr->assignedTo->name }} @endif
                                </div>
                            </div>
                            <div class="text-muted flex-shrink-0" style="font-size:.7rem">{{ $sr->updated_at->diffForHumans() }}</div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
        @endif

    </div>

    {{-- RIGHT ─────────────────────────────────────────────── --}}
    <div class="col-lg-5">

        @if(!$isClient && $rejected > 0)
        <div class="alert alert-danger d-flex align-items-center gap-2 border-0 mb-4" style="border-radius:var(--radius-lg);font-size:.85rem">
            <i class="bi bi-x-octagon-fill flex-shrink-0"></i>
            <span><strong>{{ $rejected }}</strong> {{ $rejected > 1 ? __('rejected requests require attention.') : __('rejected request require attention.') }}</span>
        </div>
        @endif

        {{-- Recent Activity — employees & admins only --}}
        @if(!$isClient)
        <div class="page-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                    <i class="bi bi-clock-history me-1"></i>{{ __('Recent Activity') }}
                </h6>
                @can('manage_users')
                <a href="{{ route('admin.audit-log.index') }}" class="text-muted" style="font-size:.75rem">
                    {{ __('Full log') }} <i class="bi bi-arrow-right"></i>
                </a>
                @endcan
            </div>

            @if($recentActivity->isEmpty())
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-clock-history d-block mb-2" style="font-size:1.5rem;opacity:.3"></i>
                    {{ __('No activity yet.') }}
                </div>
            @else
                <div class="d-flex flex-column gap-3">
                    @foreach($recentActivity as $log)
                        @php
                            [$icon, $color, $label] = $actionLabels[$log->action]
                                ?? ['bi-circle', 'text-secondary', ucwords(str_replace('_',' ',$log->action))];
                            $actor = $actors[$log->user] ?? null;
                        @endphp
                        <div class="d-flex align-items-start gap-2">
                            <i class="bi {{ $icon }} {{ $color }} flex-shrink-0" style="font-size:.9rem;margin-top:.15rem"></i>
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="small">
                                    <span class="fw-500">{{ $label }}</span>
                                    @if($actor) <span class="text-muted">{{ __('by') }} {{ $actor }}</span> @endif
                                    @if(isset($log->changes['title']))
                                        <span class="text-muted">— {{ Str::limit($log->changes['title'], 28) }}</span>
                                    @elseif(isset($log->changes['after']['title']))
                                        <span class="text-muted">— {{ Str::limit($log->changes['after']['title'], 28) }}</span>
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:.7rem">{{ $log->created_at->diffForHumans() }}</div>
                            </div>
                            @if($log->subject_type === 'App\Models\ServiceRequest' && $log->subject_id)
                            <a href="{{ route('service-requests.show', $log->subject_id) }}"
                               class="btn btn-outline-secondary btn-sm btn-action flex-shrink-0" style="padding:.15rem .4rem">
                                <i class="bi bi-arrow-right" style="font-size:.7rem"></i>
                            </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endif

    </div>
</div>

@endsection

@push('scripts')
@if(!$isClient && $total > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter', 'Cairo', system-ui, sans-serif";
Chart.defaults.font.size   = 11;
Chart.defaults.color       = '#6b7280';

// ── Monthly bar chart ──────────────────────────────────────────
new Chart(document.getElementById('chartMonthly'), {
    type: 'bar',
    data: {
        labels: @json($monthlyLabels),
        datasets: [{
            label: '{{ __("Requests") }}',
            data: @json($monthlyData),
            backgroundColor: 'rgba(37,99,235,.15)',
            borderColor: '#2563eb',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0,0,0,.04)' } },
            x: { grid: { display: false } }
        }
    }
});

// ── Stage breakdown doughnut ───────────────────────────────────
new Chart(document.getElementById('chartStages'), {
    type: 'doughnut',
    data: {
        labels: [
            '{{ __("New Request") }}',
            '{{ __("Review") }}',
            '{{ __("Preparation") }}',
            '{{ __("Client Approval") }}',
            '{{ __("Execution") }}',
            '{{ __("Monitoring") }}',
            '{{ __("Closure") }}'
        ],
        datasets: [{
            data: [
                {{ $byStage[1] ?? 0 }},
                {{ $byStage[2] ?? 0 }},
                {{ $byStage[3] ?? 0 }},
                {{ $byStage[4] ?? 0 }},
                {{ $byStage[5] ?? 0 }},
                {{ $byStage[6] ?? 0 }},
                {{ $byStage[7] ?? 0 }}
            ],
            backgroundColor: ['#e2e8f0','#a5f3fc','#bfdbfe','#fef08a','#93c5fd','#bbf7d0','#6ee7b7'],
            borderColor:      ['#94a3b8','#0891b2','#2563eb','#ca8a04','#1d4ed8','#16a34a','#059669'],
            borderWidth: 1.5,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { size: 10 } } }
        }
    }
});
</script>
@endif
@endpush
