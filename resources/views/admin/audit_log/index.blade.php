@extends('layouts.app')
@section('title', 'Audit Log')

@php
    $actionLabels = [
        // ── Generic CRUD ──────────────────────────────────────────
        'created'                  => ['bg-success',   'bi-plus-circle',           'Created'],
        'updated'                  => ['bg-primary',   'bi-pencil',                'Updated'],
        'deleted'                  => ['bg-danger',    'bi-trash',                 'Deleted'],
        'restored'                 => ['bg-info',      'bi-arrow-counterclockwise','Restored'],
        'permanently_deleted'      => ['bg-dark',      'bi-x-octagon',             'Permanently Deleted'],

        // ── Workflow / Stage ──────────────────────────────────────
        'stage_advanced'           => ['bg-success',   'bi-arrow-right-circle',    'Stage Advanced'],
        'stage_returned'           => ['bg-warning',   'bi-arrow-left-circle',     'Stage Returned'],
        'stage_force_transitioned' => ['bg-danger',    'bi-skip-forward-circle',   'Force Moved'],
        'stage_status_changed'     => ['bg-primary',   'bi-arrow-repeat',          'Status Changed'],
        'request_rejected'         => ['bg-danger',    'bi-x-circle',              'Rejected'],

        // ── Follow-ups ────────────────────────────────────────────
        'follow_up_created'        => ['bg-success',   'bi-plus-circle-dotted',    'Follow-Up Added'],
        'follow_up_updated'        => ['bg-primary',   'bi-pencil-square',         'Follow-Up Edited'],
        'follow_up_deleted'        => ['bg-danger',    'bi-dash-circle',           'Follow-Up Removed'],
        'follow_up_completed'      => ['bg-success',   'bi-check-circle',          'Step Completed'],
        'follow_up_reopened'       => ['bg-warning',   'bi-arrow-repeat',          'Step Reopened'],

        // ── Services ─────────────────────────────────────────────
        'service_added'            => ['bg-success',   'bi-grid-plus',             'Service Added'],
        'service_updated'          => ['bg-primary',   'bi-grid',                  'Service Updated'],
        'service_removed'          => ['bg-danger',    'bi-grid-x',                'Service Removed'],

        // ── Attachments ───────────────────────────────────────────
        'attachment_uploaded'      => ['bg-success',   'bi-paperclip',             'File Uploaded'],
        'attachment_deleted'       => ['bg-danger',    'bi-paperclip',             'File Deleted'],
    ];

    $subjectLabels = [
        'App\Models\ServiceRequest' => ['bi-file-text',        'Service Request'],
        'App\Models\FollowUp'       => ['bi-diagram-3',        'Follow-Up Step'],
    ];
@endphp

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Audit Log</h1>
        <p class="text-muted small mb-0">Full history of all system activity</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Admin Panel
    </a>
</div>

{{-- Filters --}}
<div class="page-card mb-4">
    <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label small">Search</label>
            <input type="text" name="search" class="form-control form-control-sm"
                   value="{{ request('search') }}" placeholder="Action, type…">
        </div>
        <div class="col-md-4">
            <label class="form-label small">Action</label>
            <select name="action" class="form-select form-select-sm">
                <option value="">— All Actions —</option>
                @foreach($actions as $act)
                    <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>
                        {{ $actionLabels[$act][2] ?? ucwords(str_replace('_', ' ', $act)) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                <i class="bi bi-search me-1"></i>Filter
            </button>
            <a href="{{ route('admin.audit-log.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
    </form>
</div>

{{-- Log Table --}}
<div class="bg-white rounded-3 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr class="bg-light border-bottom">
                    <th class="ps-4 text-muted small" style="width:16%">When</th>
                    <th class="text-muted small" style="width:18%">Who</th>
                    <th class="text-muted small" style="width:18%">Action</th>
                    <th class="text-muted small" style="width:18%">Subject</th>
                    <th class="text-muted small pe-4">Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    @php
                        [$badgeClass, $badgeIcon, $badgeLabel] = $actionLabels[$log->action]
                            ?? ['bg-secondary', 'bi-circle', ucwords(str_replace('_', ' ', $log->action))];
                        [$subjectIcon, $subjectLabel] = $subjectLabels[$log->subject_type]
                            ?? ['bi-box', class_basename($log->subject_type)];
                        $actor = $users[$log->user] ?? null;

                        $srId = null;
                        if ($log->subject_type === 'App\Models\ServiceRequest') {
                            $srId = $log->subject_id;
                        } elseif (isset($log->changes['service_request_id'])) {
                            $srId = $log->changes['service_request_id'];
                        }
                    @endphp
                    <tr class="border-bottom">
                        <td class="ps-4">
                            <div class="small fw-500">{{ $log->created_at->format('d M Y') }}</div>
                            <div class="text-muted" style="font-size:.72rem">{{ $log->created_at->format('H:i:s') }}</div>
                        </td>
                        <td>
                            @if($actor)
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                         style="width:1.75rem;height:1.75rem;font-size:.72rem;flex-shrink:0">
                                        {{ strtoupper(substr($actor->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="small fw-500">{{ $actor->name }}</div>
                                        <div class="text-muted" style="font-size:.7rem">{{ $actor->email }}</div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small">System</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $badgeClass }}" style="font-size:.72rem">
                                <i class="bi {{ $badgeIcon }} me-1"></i>{{ $badgeLabel }}
                            </span>
                        </td>
                        <td>
                            <span class="text-muted small">
                                <i class="bi {{ $subjectIcon }} me-1"></i>{{ $subjectLabel }}
                                <span class="text-muted">#{{ $log->subject_id }}</span>
                            </span>
                        </td>
                        <td class="pe-4">
                            <div class="d-flex align-items-center gap-2">
                                @if(isset($log->changes['title']))
                                    <span class="text-muted small text-truncate" style="max-width:200px">
                                        {{ $log->changes['title'] }}
                                    </span>
                                @elseif(isset($log->changes['after']['title']))
                                    <span class="text-muted small text-truncate" style="max-width:200px">
                                        {{ $log->changes['after']['title'] }}
                                    </span>
                                @elseif(isset($log->changes['before']))
                                    <span class="text-muted small">Field changes recorded</span>
                                @endif

                                @if($srId)
                                    <a href="{{ route('admin.audit-log.show', $srId) }}"
                                       class="btn btn-outline-secondary btn-sm ms-auto btn-action"
                                       title="View Request Audit">
                                        <i class="bi bi-arrow-right"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-5">
                            <i class="bi bi-clock-history d-block mb-2" style="font-size:1.5rem;opacity:.3"></i>
                            No activity found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
        <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <span class="text-muted" style="font-size:.8rem">
                Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries
            </span>
            <nav aria-label="Audit log pagination">
                <ul class="pagination pagination-sm mb-0 gap-1">
                    {{-- Previous --}}
                    @if($logs->onFirstPage())
                        <li class="page-item disabled">
                            <span class="page-link rounded-2" style="font-size:.8rem;padding:.3rem .65rem">
                                <i class="bi bi-chevron-left" style="font-size:.7rem"></i>
                            </span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link rounded-2" href="{{ $logs->previousPageUrl() }}"
                               style="font-size:.8rem;padding:.3rem .65rem" aria-label="Previous">
                                <i class="bi bi-chevron-left" style="font-size:.7rem"></i>
                            </a>
                        </li>
                    @endif

                    {{-- Page numbers --}}
                    @foreach($logs->getUrlRange(max(1, $logs->currentPage() - 2), min($logs->lastPage(), $logs->currentPage() + 2)) as $page => $url)
                        @if($page == $logs->currentPage())
                            <li class="page-item active">
                                <span class="page-link rounded-2" style="font-size:.8rem;padding:.3rem .65rem">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link rounded-2" href="{{ $url }}"
                                   style="font-size:.8rem;padding:.3rem .65rem">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if($logs->hasMorePages())
                        <li class="page-item">
                            <a class="page-link rounded-2" href="{{ $logs->nextPageUrl() }}"
                               style="font-size:.8rem;padding:.3rem .65rem" aria-label="Next">
                                <i class="bi bi-chevron-right" style="font-size:.7rem"></i>
                            </a>
                        </li>
                    @else
                        <li class="page-item disabled">
                            <span class="page-link rounded-2" style="font-size:.8rem;padding:.3rem .65rem">
                                <i class="bi bi-chevron-right" style="font-size:.7rem"></i>
                            </span>
                        </li>
                    @endif
                </ul>
            </nav>
        </div>
    @endif
</div>

@endsection
