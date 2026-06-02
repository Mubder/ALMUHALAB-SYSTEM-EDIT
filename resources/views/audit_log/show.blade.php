@extends('layouts.app')
@section('title', 'Audit — ' . Str::limit($serviceRequest->title, 40))

@php
    $actionLabels = [
        'created'              => ['bg-success',   'bi-plus-circle',           'Created'],
        'updated'              => ['bg-primary',   'bi-pencil',                'Updated'],
        'deleted'              => ['bg-danger',    'bi-trash',                 'Soft Deleted'],
        'restored'             => ['bg-info',      'bi-arrow-counterclockwise','Restored'],
        'permanently_deleted'  => ['bg-dark',      'bi-x-octagon',             'Permanently Deleted'],
        'follow_up_created'    => ['bg-success',   'bi-plus-circle-dotted',    'Follow-Up Added'],
        'follow_up_updated'    => ['bg-primary',   'bi-pencil-square',         'Follow-Up Edited'],
        'follow_up_deleted'    => ['bg-danger',    'bi-dash-circle',           'Follow-Up Removed'],
        'follow_up_completed'  => ['bg-success',   'bi-check-circle',          'Step Completed'],
        'follow_up_reopened'   => ['bg-warning',   'bi-arrow-repeat',          'Step Reopened'],
    ];
@endphp

@section('content')
<div class="row justify-content-center">
<div class="col-lg-9">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('admin.audit-log.index') }}">Audit Log</a></li>
            <li class="breadcrumb-item active">{{ Str::limit($serviceRequest->title, 40) }}</li>
        </ol>
    </nav>

    {{-- Header --}}
    <div class="page-card mb-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h1 class="h4 fw-bold mb-1">
                    <i class="bi bi-clock-history text-primary me-2"></i>Audit Trail
                </h1>
                <div class="text-muted small">
                    Request: <a href="{{ route('service-requests.show', $serviceRequest) }}" class="fw-500">
                        {{ $serviceRequest->title }}
                    </a>
                    &nbsp;·&nbsp; {{ $logs->count() }} {{ Str::plural('event', $logs->count()) }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('service-requests.show', $serviceRequest) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-diagram-3 me-1"></i>View Timeline
                </a>
                <a href="{{ route('admin.audit-log.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>All Logs
                </a>
            </div>
        </div>
    </div>

    {{-- Audit Timeline --}}
    @if($logs->isEmpty())
        <div class="page-card text-center py-5 text-muted">
            <i class="bi bi-clock-history d-block mb-2" style="font-size:2rem;opacity:.3"></i>
            No audit events recorded for this request.
        </div>
    @else
        <div class="tl-wrapper">
            @foreach($logs as $i => $log)
                @php
                    [$badgeClass, $badgeIcon, $badgeLabel] = $actionLabels[$log->action]
                        ?? ['bg-secondary', 'bi-circle', ucwords(str_replace('_', ' ', $log->action))];
                    $actor  = $users[$log->user] ?? null;
                    $isLast = $i === $logs->count() - 1;

                    // Determine what changed for before/after display
                    $before = $log->changes['before'] ?? null;
                    $after  = $log->changes['after']  ?? null;

                    // Pick meaningful diff fields to show
                    $diffFields = [];
                    if ($before && $after) {
                        $watchFields = ['title','description','status','status_type','scheduled_at','is_completed','is_visible_to_client'];
                        foreach ($watchFields as $field) {
                            if (isset($before[$field], $after[$field]) && $before[$field] !== $after[$field]) {
                                $diffFields[$field] = [$before[$field], $after[$field]];
                            }
                        }
                    }
                @endphp

                <div class="tl-item">
                    <div class="tl-left">
                        <div class="tl-marker tl-marker-audit {{ $badgeClass }}">
                            <i class="bi {{ $badgeIcon }}"></i>
                        </div>
                        @if(!$isLast)
                            <div class="tl-connector"></div>
                        @endif
                    </div>

                    <div class="tl-body">
                        <div class="tl-card">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge {{ $badgeClass }}" style="font-size:.72rem">
                                        <i class="bi {{ $badgeIcon }} me-1"></i>{{ $badgeLabel }}
                                    </span>
                                    @if($log->subject_type === 'App\Models\FollowUp')
                                        <span class="badge bg-light text-dark border" style="font-size:.7rem">
                                            <i class="bi bi-diagram-3 me-1"></i>Follow-Up #{{ $log->subject_id }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-muted" style="font-size:.72rem">
                                    <i class="bi bi-clock me-1"></i>{{ $log->created_at->format('d M Y H:i:s') }}
                                    <span class="text-muted ms-1">({{ $log->created_at->diffForHumans() }})</span>
                                </div>
                            </div>

                            {{-- Actor --}}
                            <div class="d-flex align-items-center gap-2 mb-2">
                                @if($actor)
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                         style="width:1.5rem;height:1.5rem;font-size:.65rem;flex-shrink:0">
                                        {{ strtoupper(substr($actor->name, 0, 1)) }}
                                    </div>
                                    <span class="small fw-500">{{ $actor->name }}</span>
                                    <span class="text-muted" style="font-size:.72rem">{{ $actor->email }}</span>
                                @else
                                    <span class="text-muted small">System action</span>
                                @endif
                            </div>

                            {{-- What changed --}}
                            @if(count($diffFields))
                                <div class="mt-2 border-top pt-2">
                                    <div class="text-muted mb-2" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em">Changes</div>
                                    <div class="d-flex flex-column gap-1">
                                        @foreach($diffFields as $field => [$oldVal, $newVal])
                                            <div class="d-flex align-items-start gap-2 small">
                                                <code class="text-muted px-1 rounded" style="font-size:.7rem;background:#f8f9fa;white-space:nowrap">
                                                    {{ str_replace('_', ' ', $field) }}
                                                </code>
                                                <div class="d-flex align-items-center gap-1 flex-wrap min-w-0">
                                                    <span class="text-danger text-decoration-line-through text-truncate" style="max-width:180px"
                                                          title="{{ $oldVal }}">
                                                        {{ Str::limit((string)$oldVal, 60) ?: '(empty)' }}
                                                    </span>
                                                    <i class="bi bi-arrow-right text-muted" style="font-size:.7rem;flex-shrink:0"></i>
                                                    <span class="text-success text-truncate" style="max-width:180px"
                                                          title="{{ $newVal }}">
                                                        {{ Str::limit((string)$newVal, 60) ?: '(empty)' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif(isset($log->changes['title']))
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-file-text me-1"></i>{{ $log->changes['title'] }}
                                </div>
                            @elseif(isset($log->changes['is_completed']))
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Step {{ $log->changes['is_completed'] ? 'marked complete' : 'reopened' }}:
                                    {{ $log->changes['title'] ?? '' }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
</div>

<style>
.tl-marker-audit { color: #fff; }
</style>
@endsection
