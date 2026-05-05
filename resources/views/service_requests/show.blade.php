@extends('layouts.app')
@section('title', $serviceRequest->title)

@php
    use App\Services\WorkflowService;

    $user           = auth()->user();
    $canManage      = $user->hasPermission('manage_followups');
    $canTransition  = $user->hasPermission('transition_stage');
    $canUpdateStatus= $user->hasPermission('update_status') || $canTransition;
    $canForce       = $user->hasPermission('force_transition');
    $canAssign      = $user->hasPermission('manage_assignments');
    $canEdit        = $user->hasPermission('edit_request') || $serviceRequest->user_id === $user->id;
    $canDelete      = $user->hasPermission('delete_request') || $serviceRequest->user_id === $user->id;
    $canAudit             = $user->hasPermission('view_audit_log');
    $canSeeInternal       = $user->hasPermission('view_all_comments') || $user->hasPermission('transition_stage');
    $isClient             = $serviceRequest->user_id === $user->id && !$canTransition;
    $canManageAttachments = $user->hasPermission('manage_attachments');
    $canViewAttachments   = $user->hasPermission('view_attachments') || $canManageAttachments;
    $stageAttachments     = ($canViewAttachments || $isClient)
        ? $serviceRequest->stageAttachments()->with('uploader')->orderBy('stage')->orderBy('created_at', 'desc')->get()
            ->filter(fn($a) => $a->isVisibleTo($user, $serviceRequest))
        : collect();

    $stages         = WorkflowService::STAGES;
    $currentStage   = $serviceRequest->current_stage ?? 1;
    $stageStatus    = $serviceRequest->stage_status ?? 'New';
    $currentCfg     = WorkflowService::stage($currentStage);

    // Comments visible to this user
    $comments = $serviceRequest->comments()
        ->get()
        ->filter(fn($c) => $c->isVisibleTo($user));

    // Follow-ups
    $allFollowUps    = $canManage
        ? $serviceRequest->followUps()->with('creator')->get()
        : $serviceRequest->followUps()->where('is_visible_to_client', true)->with('creator')->get();
    $currentFollowUp = $allFollowUps->where('is_completed', false)->first();

    // Services
    $canManageServices = $user->hasPermission('manage_services');
    $arrangedServices  = $serviceRequest->requestServices;
    $clientVisible     = $arrangedServices;

    // Suggested services
    $followUpStage = $serviceRequest->followUps()
        ->where('is_completed', false)
        ->orderBy('scheduled_at')->orderBy('created_at')
        ->value('status_type');
    $addedServiceIds   = $arrangedServices->pluck('service_catalog_id')->toArray();
    $suggestedServices = $followUpStage
        ? \App\Models\StageServiceMapping::where('status_type', $followUpStage)
            ->with('service')->get()
            ->map(fn($m) => $m->service)
            ->filter(fn($s) => $s && $s->is_active && !in_array($s->id, $addedServiceIds))
            ->values()
        : collect();
    $allCatalogServices = \App\Models\ServiceCatalog::where('is_active', true)->orderBy('name')->get();

    // Status badge config (for header only — uses old status field)
    $statusConfig = [
        'New'          => ['bg-primary',   'bi-inbox',        'New'],
        'Under Review' => ['bg-info',      'bi-eye',          'Under Review'],
        'Approved'     => ['bg-success',   'bi-check-circle', 'Approved'],
        'Rejected'     => ['bg-danger',    'bi-x-circle',     'Rejected'],
        'Completed'    => ['bg-secondary', 'bi-check-all',    'Completed'],
    ];
    [$badgeClass, $badgeIcon, $badgeLabel] = $statusConfig[$serviceRequest->status]
        ?? ['bg-light text-dark', 'bi-circle', $serviceRequest->status];

    // Employees for assignment dropdown
    $employees = $canAssign
        ? \App\Models\User::whereHas('role', fn($q) =>
            $q->whereHas('permissions', fn($p) => $p->where('name','transition_stage'))
          )->orderBy('name')->get()
        : collect();
@endphp

@section('content')
<div class="row justify-content-center">
<div class="col-lg-11">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('service-requests.index') }}">Requests</a></li>
            <li class="breadcrumb-item active">{{ Str::limit($serviceRequest->title, 40) }}</li>
        </ol>
    </nav>

    {{-- ── Header ──────────────────────────────────────────── --}}
    <div class="page-card mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    @if($serviceRequest->is_rejected)
                        <span class="badge bg-danger px-3 py-2 fs-6">
                            <i class="bi bi-x-octagon me-1"></i>Rejected
                        </span>
                    @else
                        <span class="badge bg-{{ $currentCfg['color'] }} px-3 py-2 fs-6">
                            <i class="bi {{ $currentCfg['icon'] }} me-1"></i>{{ $currentCfg['label'] }}
                        </span>
                        <span class="badge bg-light text-dark border">{{ $stageStatus }}</span>
                    @endif
                    @if($serviceRequest->serviceType->name !== '—')
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-tag me-1"></i>{{ $serviceRequest->serviceType->name }}
                        </span>
                    @endif
                    @if($canTransition && $serviceRequest->assignedTo?->id)
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-person-badge me-1"></i>{{ $serviceRequest->assignedTo->name }}
                        </span>
                    @endif
                </div>
                <h2 class="h4 fw-bold mb-1 mt-2">{{ $serviceRequest->title }}</h2>
                <div class="text-muted small">
                    <i class="bi bi-calendar3 me-1"></i>Submitted {{ $serviceRequest->created_at->format('d M Y') }}
                    @if($canTransition)
                        &nbsp;·&nbsp;<i class="bi bi-person me-1"></i>{{ $serviceRequest->user->name }}
                    @endif
                    @if($canAudit)
                        &nbsp;·&nbsp;
                        <a href="{{ route('admin.audit-log.show', $serviceRequest) }}" class="text-muted">
                            <i class="bi bi-clock-history me-1"></i>Audit Log
                        </a>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if($canEdit && !$serviceRequest->isClosed())
                    <a href="{{ route('service-requests.edit', $serviceRequest) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                @endif
                @if($canDelete)
                    <form action="{{ route('service-requests.destroy', $serviceRequest) }}" method="POST"
                          onsubmit="return confirm('Move to trash?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Stage Progress Bar ───────────────────────────────── --}}
    <div class="page-card mb-4 py-3">
        <div class="stage-progress">
            @foreach($stages as $num => $cfg)
                @php
                    $isPast    = $num < $currentStage;
                    $isCurrent = $num === $currentStage;
                    $isFuture  = $num > $currentStage;
                    $isRejected= $serviceRequest->is_rejected && $isCurrent;
                @endphp
                <div class="stage-step {{ $isPast ? 'past' : ($isCurrent ? 'current' : 'future') }} {{ $isRejected ? 'rejected' : '' }}">
                    <div class="stage-circle">
                        @if($isRejected)
                            <i class="bi bi-x-lg"></i>
                        @elseif($isPast)
                            <i class="bi bi-check-lg"></i>
                        @else
                            <i class="bi {{ $cfg['icon'] }}"></i>
                        @endif
                    </div>
                    <div class="stage-label">
                        <div class="stage-num">{{ $num }}</div>
                        <div class="stage-name">{{ $cfg['label'] }}</div>
                        @if($isCurrent && !$serviceRequest->is_rejected)
                            <div class="stage-status-badge">{{ $stageStatus }}</div>
                        @endif
                    </div>
                    @if($num < count($stages))
                        <div class="stage-connector {{ $isPast ? 'done' : '' }}"></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="row g-4">

        {{-- ── LEFT COLUMN ─────────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- ── Closed / Rejected state card (non-admin view) ─────── --}}
            @if($serviceRequest->isClosed() && !$canForce)
            <div class="page-card mb-4 border border-2 {{ $serviceRequest->is_rejected ? 'border-danger' : 'border-secondary' }}">
                <div class="d-flex align-items-center gap-3">
                    <div class="{{ $serviceRequest->is_rejected ? 'text-danger' : 'text-secondary' }}" style="font-size:2rem;flex-shrink:0">
                        <i class="bi {{ $serviceRequest->is_rejected ? 'bi-x-octagon-fill' : 'bi-check-circle-fill' }}"></i>
                    </div>
                    <div>
                        <h6 class="fw-700 mb-1 {{ $serviceRequest->is_rejected ? 'text-danger' : 'text-secondary' }}">
                            {{ $serviceRequest->is_rejected ? 'Request Rejected' : 'Request Closed' }}
                        </h6>
                        <p class="text-muted small mb-0">
                            @if($serviceRequest->is_rejected)
                                This request was rejected at the Client Approval stage. No further actions are available.
                            @else
                                This request has been closed and is now complete.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Workflow Controls ────────────────────────────────── --}}
            @php
                $isClosed = $serviceRequest->isClosed();
                // Show normal controls when open; show override controls for force-users even when closed
                $showStageControls = (!$isClosed && ($canTransition || $canUpdateStatus || $canAssign))
                                  || ($isClosed && $canForce);
            @endphp

            @if($showStageControls)
            <div class="page-card mb-4 border-start border-3 border-{{ $isClosed ? 'danger' : $currentCfg['color'] }}">

                {{-- Override warning for admins on closed/rejected requests --}}
                @if($isClosed)
                <div class="alert d-flex align-items-start gap-2 py-2 mb-3
                            {{ $serviceRequest->is_rejected ? 'alert-danger' : 'alert-secondary' }}"
                     style="font-size:.82rem">
                    <i class="bi {{ $serviceRequest->is_rejected ? 'bi-x-octagon' : 'bi-lock' }} flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>{{ $serviceRequest->is_rejected ? 'Rejected' : 'Closed' }} — Admin Override Active</strong><br>
                        This request is {{ $serviceRequest->is_rejected ? 'rejected' : 'closed' }}.
                        You can update the status to un-reject it, or use <strong>Force Move</strong> to re-open it at any stage.
                    </div>
                </div>
                @endif

                <h6 class="fw-bold mb-3 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                    <i class="bi bi-sliders me-1"></i>Stage Controls — {{ $currentCfg['label'] }}
                </h6>

                <div class="row g-3">

                    {{-- Update Status within Stage --}}
                    @if($canUpdateStatus && (!$isClosed || $canForce))
                    <div class="col-md-6">
                        <form action="{{ route('workflow.status', $serviceRequest) }}" method="POST">
                            @csrf
                            <label class="form-label small fw-600">Update Stage Status</label>
                            <div class="input-group input-group-sm">
                                <select name="stage_status" class="form-select">
                                    @foreach($currentCfg['statuses'] as $s)
                                        <option value="{{ $s }}" {{ $stageStatus === $s ? 'selected' : '' }}>{{ $s }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary px-3">Apply</button>
                            </div>
                            <input type="hidden" name="notes" value="">
                        </form>
                    </div>
                    @endif

                    {{-- Notes (only for transitions, only when not closed) --}}
                    @if($canTransition && !$isClosed)
                    <div class="col-md-6">
                        <label class="form-label small fw-600">Notes for transition</label>
                        <input type="text" id="transitionNotes" class="form-control form-control-sm"
                               placeholder="Optional reason or note…">
                    </div>
                    @endif

                    {{-- Advance (only when not closed) --}}
                    @if($canTransition && !$isClosed && !$serviceRequest->isAtFinalStage())
                    <div class="col-auto">
                        <form action="{{ route('workflow.advance', $serviceRequest) }}" method="POST">
                            @csrf
                            <input type="hidden" name="notes" id="advanceNotes">
                            <button type="submit" class="btn btn-success btn-sm"
                                    onclick="document.getElementById('advanceNotes').value=(document.getElementById('transitionNotes')||{value:''}).value">
                                <i class="bi bi-arrow-right-circle me-1"></i>
                                Advance to {{ $stages[$currentStage + 1]['label'] ?? 'Next' }}
                            </button>
                        </form>
                    </div>
                    @endif

                    {{-- Return (only when not closed) --}}
                    @if($canTransition && !$isClosed && $currentStage > 1)
                    <div class="col-auto">
                        <form action="{{ route('workflow.return', $serviceRequest) }}" method="POST">
                            @csrf
                            <input type="hidden" name="notes" id="returnNotes">
                            <button type="submit" class="btn btn-warning btn-sm"
                                    onclick="document.getElementById('returnNotes').value=(document.getElementById('transitionNotes')||{value:''}).value">
                                <i class="bi bi-arrow-left-circle me-1"></i>
                                Return to {{ $stages[$currentStage - 1]['label'] ?? 'Previous' }}
                            </button>
                        </form>
                    </div>
                    @endif

                    {{-- Force Transition — always visible for canForce, even on closed/rejected --}}
                    @if($canForce)
                    <div class="col-auto">
                        <button class="btn {{ $isClosed ? 'btn-danger' : 'btn-outline-dark' }} btn-sm"
                                data-bs-toggle="modal" data-bs-target="#forceModal">
                            <i class="bi bi-lightning me-1"></i>{{ $isClosed ? 'Force Reopen' : 'Force Move' }}
                        </button>
                    </div>
                    @endif

                </div>

                {{-- Assignment (only when not closed) --}}
                @if($canAssign && !$isClosed)
                <hr class="my-3">
                <form action="{{ route('workflow.assign', $serviceRequest) }}" method="POST" class="d-flex gap-2 align-items-end">
                    @csrf
                    <div class="flex-grow-1">
                        <label class="form-label small fw-600">Assigned Employee</label>
                        <select name="assigned_to" class="form-select form-select-sm">
                            <option value="">— Unassigned —</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ $serviceRequest->assigned_to == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-person-check me-1"></i>Assign
                    </button>
                </form>
                @endif
            </div>
            @endif

            {{-- Client Actions (stage 4: Client Approval) --}}
            @if($isClient && $currentStage === 4 && !$serviceRequest->is_rejected)
            <div class="page-card mb-4 border border-warning border-2">
                <h6 class="fw-bold mb-2 text-warning"><i class="bi bi-person-check me-1"></i>Your Action Required</h6>
                <p class="text-muted small mb-3">Please review the prepared itinerary and confirm your payment or request changes.</p>
                <form action="{{ route('workflow.status', $serviceRequest) }}" method="POST" class="d-flex gap-2">
                    @csrf
                    <input type="hidden" name="stage_status" value="Paid">
                    <button type="submit" class="btn btn-success"
                            onclick="return confirm('Confirm payment and approve the request?')">
                        <i class="bi bi-check-circle me-1"></i>Confirm & Pay
                    </button>
                </form>
                <form action="{{ route('workflow.status', $serviceRequest) }}" method="POST" class="d-flex gap-2 mt-2">
                    @csrf
                    <input type="hidden" name="stage_status" value="Rejected">
                    <button type="submit" class="btn btn-outline-danger btn-sm"
                            onclick="return confirm('Reject this request? This action stops the process.')">
                        <i class="bi bi-x-circle me-1"></i>Reject
                    </button>
                </form>
            </div>
            @endif

            {{-- ── Comments ──────────────────────────────────────── --}}
            <div class="page-card mb-4" id="comments">
                <h6 class="fw-bold mb-4 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                    <i class="bi bi-chat-left-text me-1"></i>Comments
                    <span class="badge bg-light text-dark border ms-1">{{ $comments->count() }}</span>
                </h6>

                {{-- Add comment form --}}
                <form action="{{ route('stage-comments.store', $serviceRequest) }}" method="POST" class="mb-4">
                    @csrf
                    <div class="d-flex gap-2 align-items-start">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                             style="width:2rem;height:2rem;font-size:.78rem">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="flex-grow-1">
                            <textarea name="content" rows="2"
                                      class="form-control form-control-sm mb-2"
                                      placeholder="Add a comment…" required></textarea>
                            <div class="d-flex gap-2 align-items-center">
                                @if($canSeeInternal)
                                <select name="visibility" class="form-select form-select-sm" style="width:auto">
                                    @foreach(\App\Models\StageComment::VISIBILITY as $k => $v)
                                        <option value="{{ $k }}">{{ $v['label'] }}</option>
                                    @endforeach
                                </select>
                                @else
                                <input type="hidden" name="visibility" value="all">
                                @endif
                                <button type="submit" class="btn btn-primary btn-sm px-3">
                                    <i class="bi bi-send me-1"></i>Post
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Comments list --}}
                @forelse($comments as $comment)
                    @php $vcfg = $comment->visibilityConfig(); @endphp
                    <div class="comment-thread mb-3">
                        <div class="d-flex gap-2 align-items-start">
                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                 style="width:2rem;height:2rem;font-size:.78rem">
                                {{ strtoupper(substr($comment->creator->name, 0, 1)) }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="bg-light rounded-3 p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-1 flex-wrap gap-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-500 small">{{ $comment->creator->name }}</span>
                                            @if($comment->visibility !== 'all')
                                                <span class="badge bg-{{ $vcfg['color'] }}-subtle text-{{ $vcfg['color'] }} border border-{{ $vcfg['color'] }}-subtle"
                                                      style="font-size:.65rem">
                                                    <i class="bi {{ $vcfg['icon'] }} me-1"></i>{{ $vcfg['label'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-muted" style="font-size:.72rem">
                                                {{ $comment->created_at->diffForHumans() }}
                                            </span>
                                            @if($comment->created_by === $user->id || $user->hasPermission('manage_users'))
                                            <form action="{{ route('stage-comments.destroy', [$serviceRequest, $comment]) }}"
                                                  method="POST" onsubmit="return confirm('Remove comment?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-link btn-sm text-danger p-0">
                                                    <i class="bi bi-trash" style="font-size:.75rem"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="small" style="line-height:1.6;white-space:pre-wrap">{{ $comment->content }}</div>
                                </div>

                                {{-- Reply form --}}
                                <div class="mt-1">
                                    <a href="#" class="text-muted small text-decoration-none"
                                       onclick="toggleReply({{ $comment->id }}); return false;">
                                        <i class="bi bi-reply me-1"></i>Reply
                                    </a>
                                    <div class="d-none mt-2" id="reply-{{ $comment->id }}">
                                        <form action="{{ route('stage-comments.store', $serviceRequest) }}" method="POST"
                                              class="d-flex gap-2">
                                            @csrf
                                            <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                            <input type="hidden" name="visibility" value="{{ $comment->visibility }}">
                                            <input type="text" name="content" class="form-control form-control-sm"
                                                   placeholder="Write a reply…" required>
                                            <button type="submit" class="btn btn-outline-primary btn-sm">Send</button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Replies --}}
                                @foreach($comment->replies->filter(fn($r) => $r->isVisibleTo($user)) as $reply)
                                <div class="d-flex gap-2 align-items-start mt-2 ms-3">
                                    <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                         style="width:1.75rem;height:1.75rem;font-size:.7rem">
                                        {{ strtoupper(substr($reply->creator->name, 0, 1)) }}
                                    </div>
                                    <div class="bg-white border rounded-3 p-2 flex-grow-1">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-500 small">{{ $reply->creator->name }}</span>
                                            <span class="text-muted" style="font-size:.7rem">{{ $reply->created_at->diffForHumans() }}</span>
                                        </div>
                                        <div class="small" style="line-height:1.6">{{ $reply->content }}</div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-3 text-muted small">
                        <i class="bi bi-chat-left d-block mb-1" style="opacity:.3;font-size:1.5rem"></i>
                        No comments yet. Be the first to add one.
                    </div>
                @endforelse
            </div>

            {{-- ── Stage Attachments ──────────────────────────────── --}}
            @if($canManageAttachments || $stageAttachments->count())
            <div class="page-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                        <i class="bi bi-paperclip me-1"></i>Stage Attachments
                    </h6>
                    @if($canManageAttachments)
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadAttachmentModal">
                        <i class="bi bi-upload me-1"></i>Upload
                    </button>
                    @endif
                </div>

                @if($stageAttachments->isEmpty())
                    <div class="text-center py-3 text-muted small">
                        <i class="bi bi-paperclip d-block mb-1" style="opacity:.3;font-size:1.5rem"></i>
                        No attachments yet.
                    </div>
                @else
                    @php $grouped = $stageAttachments->groupBy('stage'); @endphp
                    @foreach($grouped as $stageNum => $files)
                        @php $stageCfg = \App\Services\WorkflowService::stage($stageNum); @endphp
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="badge bg-{{ $stageCfg['color'] }}-subtle text-{{ $stageCfg['color'] }} border border-{{ $stageCfg['color'] }}-subtle" style="font-size:.68rem">
                                    <i class="bi {{ $stageCfg['icon'] }} me-1"></i>{{ $stageCfg['label'] }}
                                </span>
                            </div>
                            <div class="d-flex flex-column gap-2">
                                @foreach($files as $att)
                                    @php $vcfg = $att->visibilityConfig(); @endphp
                                    <div class="d-flex align-items-center gap-2 p-2 rounded-2 border bg-light">
                                        <i class="bi {{ $att->fileIcon() }} fs-5 flex-shrink-0"></i>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <a href="{{ $att->url() }}" target="_blank"
                                               class="fw-500 small text-truncate d-block text-decoration-none text-dark"
                                               title="{{ $att->original_name }}">
                                                {{ $att->original_name }}
                                            </a>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span class="text-muted" style="font-size:.7rem">{{ $att->humanSize() }}</span>
                                                <span class="text-muted" style="font-size:.7rem">· {{ $att->created_at->format('d M Y') }}</span>
                                                <span class="text-muted" style="font-size:.7rem">· {{ $att->uploader->name }}</span>
                                                @if($canViewAttachments)
                                                    <span class="badge bg-{{ $vcfg['color'] }}-subtle text-{{ $vcfg['color'] }} border border-{{ $vcfg['color'] }}-subtle" style="font-size:.62rem">
                                                        <i class="bi {{ $vcfg['icon'] }} me-1"></i>{{ $vcfg['label'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <a href="{{ $att->url() }}" download="{{ $att->original_name }}"
                                               class="btn btn-outline-secondary btn-sm btn-action" title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            @if($canManageAttachments)
                                            <form action="{{ route('stage-attachments.destroy', [$serviceRequest, $att]) }}"
                                                  method="POST" onsubmit="return confirm('Remove this file?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm btn-action" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Upload Attachment Modal --}}
            @if($canManageAttachments)
            <div class="modal fade" id="uploadAttachmentModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <h6 class="modal-title fw-bold"><i class="bi bi-upload text-primary me-2"></i>Upload Attachments</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('stage-attachments.store', $serviceRequest) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Files <span class="text-danger">*</span></label>
                                        <input type="file" name="files[]" class="form-control" multiple required
                                               accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.zip,.txt">
                                        <div class="form-text">PDF, images, Word, Excel, ZIP — max 20 MB each.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Stage</label>
                                        <select name="stage" class="form-select form-select-sm">
                                            @foreach(\App\Services\WorkflowService::STAGES as $n => $cfg)
                                                <option value="{{ $n }}" {{ $n === $currentStage ? 'selected' : '' }}>
                                                    {{ $n }}. {{ $cfg['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Visibility</label>
                                        <select name="visibility" class="form-select form-select-sm">
                                            @foreach(\App\Models\StageAttachment::VISIBILITY as $k => $vcfg)
                                                <option value="{{ $k }}">{{ $vcfg['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload me-1"></i>Upload
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
            @endif

            {{-- ── Timeline ──────────────────────────────────────── --}}
            @if($allFollowUps->count() || $canManage)
            <div class="page-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                        <i class="bi bi-diagram-3 me-1"></i>Journey Timeline
                    </h6>
                    @if($canManage)
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFollowUpModal">
                            <i class="bi bi-plus-lg me-1"></i>Add Step
                        </button>
                    @endif
                </div>

                @if($allFollowUps->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-diagram-3 d-block mb-2" style="font-size:2rem;opacity:.2"></i>
                        <p class="small mb-2">No steps yet.</p>
                        @if($canManage)
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFollowUpModal">
                            <i class="bi bi-plus-lg me-1"></i>Add First Step
                        </button>
                        @else
                        <p class="small text-muted">Your request is being reviewed. Updates will appear here.</p>
                        @endif
                    </div>
                @else
                    <div class="tl-wrapper">
                        @foreach($allFollowUps as $i => $fu)
                            @php
                                $cfg       = $fu->statusConfig();
                                $isCurrent = $currentFollowUp && $fu->id === $currentFollowUp->id;
                                $isLast    = $i === $allFollowUps->count() - 1;
                                $stateClass= $fu->is_completed ? 'tl-done' : ($isCurrent ? 'tl-current' : 'tl-future');
                            @endphp
                            <div class="tl-item {{ $stateClass }}">
                                <div class="tl-left">
                                    <div class="tl-marker tl-marker-{{ $fu->is_completed ? 'done' : ($isCurrent ? 'current' : 'future') }}">
                                        @if($fu->is_completed) <i class="bi bi-check-lg"></i>
                                        @elseif($isCurrent)   <i class="bi {{ $cfg['icon'] }}"></i>
                                        @else                 <i class="bi bi-clock"></i>
                                        @endif
                                    </div>
                                    @if(!$isLast)
                                        <div class="tl-connector {{ $fu->is_completed ? 'tl-connector-done' : '' }}"></div>
                                    @endif
                                </div>
                                <div class="tl-body">
                                    <div class="tl-card {{ $isCurrent ? 'tl-card-current' : '' }} {{ $fu->is_completed ? 'tl-card-done' : '' }}">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                            <div class="d-flex gap-2 flex-wrap">
                                                <span class="badge bg-{{ $cfg['color'] }}-subtle text-{{ $cfg['color'] }} border border-{{ $cfg['color'] }}-subtle" style="font-size:.7rem">
                                                    <i class="bi {{ $cfg['icon'] }} me-1"></i>{{ $cfg['label'] }}
                                                </span>
                                                @if(!$fu->is_visible_to_client && $canManage)
                                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size:.7rem">
                                                        <i class="bi bi-eye-slash me-1"></i>Private
                                                    </span>
                                                @endif
                                                @if($isCurrent)
                                                    <span class="badge bg-primary" style="font-size:.7rem">Current</span>
                                                @endif
                                            </div>
                                            <span class="text-muted" style="font-size:.72rem">
                                                @if($fu->scheduled_at)
                                                    <i class="bi bi-calendar-event me-1"></i>{{ $fu->scheduled_at->format('d M Y') }}
                                                @else
                                                    <i class="bi bi-clock me-1"></i>{{ $fu->created_at->format('d M Y') }}
                                                @endif
                                            </span>
                                        </div>
                                        <div class="fw-600 mb-1" style="font-size:.95rem">{{ $fu->title }}</div>
                                        @if($fu->description)
                                            <div class="text-muted small" style="white-space:pre-wrap;line-height:1.6">{{ $fu->description }}</div>
                                        @endif
                                        @if($fu->extra_data && count($fu->extra_data))
                                            <div class="mt-2 pt-2 border-top row g-2">
                                                @foreach($fu->extra_data as $k => $v)
                                                    @if($v)
                                                    <div class="col-sm-6">
                                                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase">{{ ucwords(str_replace('_',' ',$k)) }}</div>
                                                        <div class="small fw-500">{{ $v }}</div>
                                                    </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                        @if($canManage)
                                            <div class="mt-3 pt-2 border-top d-flex gap-2 flex-wrap align-items-center">
                                                <form action="{{ route('follow-ups.toggle', [$serviceRequest, $fu]) }}" method="POST">
                                                    @csrf @method('PATCH')
                                                    <button type="submit" class="btn btn-sm {{ $fu->is_completed ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                                        <i class="bi {{ $fu->is_completed ? 'bi-arrow-counterclockwise' : 'bi-check-circle' }} me-1"></i>
                                                        {{ $fu->is_completed ? 'Reopen' : 'Mark Complete' }}
                                                    </button>
                                                </form>
                                                <a href="{{ route('follow-ups.edit', [$serviceRequest, $fu]) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil me-1"></i>Edit
                                                </a>
                                                <form action="{{ route('follow-ups.destroy', [$serviceRequest, $fu]) }}" method="POST"
                                                      onsubmit="return confirm('Remove this step?')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                                <span class="text-muted ms-auto" style="font-size:.7rem">by {{ $fu->creator->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @endif

        </div>{{-- /col-lg-8 --}}

        {{-- ── RIGHT COLUMN ─────────────────────────────────── --}}
        <div class="col-lg-4">

            {{-- Stage Summary --}}
            <div class="page-card mb-4">
                <h6 class="fw-bold mb-3 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                    <i class="bi bi-broadcast me-1"></i>Current Stage
                </h6>
                <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light border mb-3">
                    <div class="text-{{ $currentCfg['color'] }}" style="font-size:1.75rem">
                        <i class="bi {{ $currentCfg['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="fw-bold">{{ $currentCfg['label'] }}</div>
                        <div class="small text-muted">{{ $stageStatus }}</div>
                        @if($serviceRequest->stage_entered_at)
                        <div class="text-muted" style="font-size:.72rem">
                            <i class="bi bi-clock me-1"></i>{{ $serviceRequest->stageDaysElapsed() }} day(s) in this stage
                        </div>
                        @endif
                    </div>
                </div>
                @if(!$serviceRequest->isAtFinalStage() && !$serviceRequest->isClosed())
                    <div class="text-muted small">
                        <i class="bi bi-arrow-right me-1"></i>Next:
                        <strong>{{ $stages[$currentStage + 1]['label'] ?? '—' }}</strong>
                    </div>
                @elseif($serviceRequest->stage_status === 'Closed')
                    <div class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>Request Closed</div>
                @endif

                {{-- Stage history (collapsible) --}}
                @if($canTransition)
                <div class="mt-3">
                    <a href="#stageHistory" class="small text-muted text-decoration-none" data-bs-toggle="collapse">
                        <i class="bi bi-clock-history me-1"></i>Stage History
                    </a>
                    <div class="collapse mt-2" id="stageHistory">
                        @foreach($serviceRequest->stageHistory as $h)
                            @php [$hl, $hc] = \App\Models\ServiceRequestStageHistory::ACTION_LABELS[$h->action] ?? ['Action', 'bg-secondary']; @endphp
                            <div class="d-flex gap-2 align-items-start mb-2 small">
                                <span class="badge {{ $hc }}" style="font-size:.65rem;flex-shrink:0">{{ $hl }}</span>
                                <div>
                                    <div class="fw-500">{{ WorkflowService::STAGES[$h->to_stage]['label'] ?? '?' }}</div>
                                    <div class="text-muted" style="font-size:.7rem">
                                        {{ $h->performer->name }} · {{ $h->created_at->format('d M H:i') }}
                                    </div>
                                    @if($h->notes)<div class="text-muted fst-italic" style="font-size:.7rem">{{ $h->notes }}</div>@endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Upcoming Follow-Up Steps --}}
            @php $upcoming = $allFollowUps->where('is_completed', false)->skip($currentFollowUp ? 1 : 0)->take(3); @endphp
            @if($upcoming->count())
            <div class="page-card mb-4">
                <h6 class="fw-bold mb-3 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                    <i class="bi bi-arrow-right-circle me-1"></i>Coming Up
                </h6>
                <div class="d-flex flex-column gap-2">
                    @foreach($upcoming as $step)
                        @php $cfg = $step->statusConfig(); @endphp
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-light border">
                            <i class="bi {{ $cfg['icon'] }} text-{{ $cfg['color'] }}" style="font-size:.9rem;flex-shrink:0"></i>
                            <div>
                                <div class="small fw-500">{{ $step->title }}</div>
                                @if($step->scheduled_at)
                                    <div class="text-muted" style="font-size:.7rem">{{ $step->scheduled_at->format('d M Y') }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Request Details (collapsible) --}}
            <div class="page-card mb-4">
                <a class="d-flex justify-content-between align-items-center text-decoration-none text-dark fw-bold"
                   data-bs-toggle="collapse" href="#requestDetails">
                    <span style="font-size:.72rem;text-transform:uppercase;letter-spacing:.07em">
                        <i class="bi bi-file-text me-1 text-muted"></i>Request Details
                    </span>
                    <i class="bi bi-chevron-down text-muted small"></i>
                </a>
                <div class="collapse mt-3" id="requestDetails">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="text-muted" style="font-size:.7rem;text-transform:uppercase">Service Type</div>
                            <div class="small fw-500">{{ $serviceRequest->serviceType->name }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted" style="font-size:.7rem;text-transform:uppercase">Request Status</div>
                            <span class="badge {{ $badgeClass }} small">{{ $badgeLabel }}</span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted" style="font-size:.7rem;text-transform:uppercase;margin-bottom:.25rem">Description</div>
                        <div class="bg-light rounded-3 p-2 small" style="white-space:pre-wrap;line-height:1.6">{{ $serviceRequest->description }}</div>
                    </div>
                    @if($serviceRequest->client_country || $serviceRequest->destination_country)
                    <div class="border-top pt-2 mb-2">
                        <div class="text-muted mb-1" style="font-size:.7rem;text-transform:uppercase"><i class="bi bi-airplane me-1"></i>Travel</div>
                        <div class="row g-1 small">
                            @if($serviceRequest->client_country)
                            <div class="col-6"><span class="text-muted">From:</span> {{ $serviceRequest->client_country }}</div>
                            @endif
                            @if($serviceRequest->destination_country)
                            <div class="col-6"><span class="text-muted">To:</span> {{ $serviceRequest->destination_country }}{{ $serviceRequest->destination_city ? ', '.$serviceRequest->destination_city : '' }}</div>
                            @endif
                            @if($serviceRequest->travel_date_start)
                            <div class="col-6"><span class="text-muted">Depart:</span> {{ $serviceRequest->travel_date_start->format('d M Y') }}</div>
                            @endif
                            @if($serviceRequest->travel_date_end)
                            <div class="col-6"><span class="text-muted">Return:</span> {{ $serviceRequest->travel_date_end->format('d M Y') }}</div>
                            @endif
                            @if($serviceRequest->durationDays())
                            <div class="col-12"><span class="text-muted">Duration:</span> <strong>{{ $serviceRequest->durationDays() }} days</strong></div>
                            @endif
                        </div>
                    </div>
                    @endif
                    @if($serviceRequest->attachments->count())
                    <div class="border-top pt-2">
                        <div class="text-muted mb-1" style="font-size:.7rem;text-transform:uppercase"><i class="bi bi-paperclip me-1"></i>Attachments</div>
                        @foreach($serviceRequest->attachments as $att)
                        <div class="d-flex align-items-center gap-2 p-1 bg-light rounded mb-1">
                            <i class="bi bi-file-earmark text-primary small"></i>
                            <span class="small text-truncate flex-grow-1">{{ $att->original_name }}</span>
                            <a href="{{ $att->url() }}" target="_blank" class="btn btn-outline-primary btn-sm btn-action py-0">
                                <i class="bi bi-download"></i>
                            </a>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

        </div>{{-- /col-lg-4 --}}
    </div>{{-- /row --}}

    {{-- ── Services Section ────────────────────────────────────── --}}
    @if($canManageServices || $clientVisible->count())
    <div class="page-card mt-2">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="fw-bold mb-0 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                <i class="bi bi-grid-3x3-gap me-1"></i>Arranged Services
            </h6>
            @if($canManageServices)
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                    <i class="bi bi-plus-lg me-1"></i>Add Service
                </button>
            @endif
        </div>

        @if($canManageServices && $suggestedServices->count())
        <div class="alert alert-info d-flex align-items-start gap-2 py-2 mb-3" style="font-size:.85rem">
            <i class="bi bi-lightbulb-fill mt-1 flex-shrink-0"></i>
            <div>
                <strong>Suggested for current stage:</strong>
                <div class="d-flex flex-wrap gap-2 mt-1">
                    @foreach($suggestedServices as $svc)
                    <button class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:.78rem"
                            onclick="prefillService({{ $svc->id }}, '{{ addslashes($svc->name) }}')">
                        <i class="bi {{ $svc->icon }} me-1"></i>{{ $svc->name }}<i class="bi bi-plus ms-1"></i>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        @if(($canManageServices ? $arrangedServices : $clientVisible)->count())
        <div class="row g-3">
            @foreach($canManageServices ? $arrangedServices : $clientVisible as $rs)
                @php $scfg = $rs->statusConfig(); @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="text-{{ $rs->service->color ?? 'primary' }}" style="font-size:1.2rem">
                                    <i class="bi {{ $rs->service->icon ?? 'bi-star' }}"></i>
                                </span>
                                <div>
                                    <div class="fw-500 small">{{ $rs->service->name }}</div>
                                    <span class="badge bg-{{ $scfg['color'] }}-subtle text-{{ $scfg['color'] }} border border-{{ $scfg['color'] }}-subtle" style="font-size:.65rem">
                                        <i class="bi {{ $scfg['icon'] }} me-1"></i>{{ $scfg['label'] }}
                                    </span>
                                </div>
                            </div>
                            @if($canManageServices)
                            <div class="d-flex gap-1">
                                <button class="btn btn-outline-secondary btn-sm btn-action"
                                        data-bs-toggle="modal" data-bs-target="#editSvc{{ $rs->id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('request-services.destroy', [$serviceRequest, $rs]) }}"
                                      method="POST" onsubmit="return confirm('Remove?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm btn-action"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                            @endif
                        </div>
                        @if($rs->scheduled_at)<div class="text-muted small"><i class="bi bi-calendar-event me-1"></i>{{ $rs->scheduled_at->format('d M Y') }}</div>@endif
                        @if($rs->reference)<div class="text-muted small"><i class="bi bi-hash me-1"></i>{{ $rs->reference }}</div>@endif
                        @if($rs->notes)<div class="text-muted" style="font-size:.78rem;line-height:1.5">{{ Str::limit($rs->notes, 80) }}</div>@endif
                    </div>
                </div>

                @if($canManageServices)
                <div class="modal fade" id="editSvc{{ $rs->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h6 class="modal-title fw-bold"><i class="bi {{ $rs->service->icon ?? 'bi-star' }} me-2"></i>Edit: {{ $rs->service->name }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="{{ route('request-services.update', [$serviceRequest, $rs]) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Status</label>
                                            <select name="status" class="form-select form-select-sm">
                                                @foreach(\App\Models\RequestService::STATUSES as $k => $cfg)
                                                    <option value="{{ $k }}" {{ $rs->status === $k ? 'selected':'' }}>{{ $cfg['label'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Scheduled Date</label>
                                            <input type="date" name="scheduled_at" class="form-control form-control-sm" value="{{ $rs->scheduled_at?->format('Y-m-d') }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Reference</label>
                                            <input type="text" name="reference" class="form-control form-control-sm" value="{{ $rs->reference }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Notes</label>
                                            <textarea name="notes" rows="2" class="form-control form-control-sm">{{ $rs->notes }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-circle me-1"></i>Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        </div>
        @else
            @if($canManageServices)
            <div class="text-center py-4 text-muted">
                <i class="bi bi-grid-3x3-gap d-block mb-2" style="font-size:1.5rem;opacity:.25"></i>
                <p class="small mb-2">No services added yet.</p>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                    <i class="bi bi-plus-lg me-1"></i>Add First Service
                </button>
            </div>
            @endif
        @endif
    </div>
    @endif

</div>
</div>

{{-- ── Force Transition Modal (admin) ─────────────────────────── --}}
@if($canForce)
<div class="modal fade" id="forceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-lightning text-warning me-2"></i>Force Stage Transition</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('workflow.force', $serviceRequest) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle me-1"></i>This bypasses the normal workflow order. Use with caution.</div>
                    <div class="mb-3">
                        <label class="form-label">Move to Stage</label>
                        <select name="to_stage" class="form-select">
                            @foreach($stages as $n => $cfg)
                                <option value="{{ $n }}" {{ $n === $currentStage ? 'disabled selected' : '' }}>
                                    {{ $n }}. {{ $cfg['label'] }} {{ $n === $currentStage ? '(current)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="notes" rows="2" class="form-control" placeholder="Required: explain why you are overriding…" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4"><i class="bi bi-lightning me-1"></i>Force Move</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ── Add Service Modal ────────────────────────────────────────── --}}
@if($canManageServices)
<div class="modal fade" id="addServiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-success me-2"></i>Add Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('request-services.store', $serviceRequest) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Service <span class="text-danger">*</span></label>
                            <select name="service_catalog_id" id="serviceCatalogSelect" class="form-select" required>
                                <option value="">— Select a service —</option>
                                @foreach($allCatalogServices as $svc)
                                    <option value="{{ $svc->id }}">{{ $svc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                @foreach(\App\Models\RequestService::STATUSES as $k => $cfg)
                                    <option value="{{ $k }}" {{ $k === 'pending' ? 'selected':'' }}>{{ $cfg['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label">Scheduled Date</label><input type="date" name="scheduled_at" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Reference / Booking No.</label><input type="text" name="reference" class="form-control" placeholder="e.g. HTL-4521"></div>
                        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control" placeholder="Any details…"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4"><i class="bi bi-plus-circle me-1"></i>Add</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ── Add Follow-Up Modal ──────────────────────────────────────── --}}
@if($canManage)
<div class="modal fade" id="addFollowUpModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Add Timeline Step</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('follow-ups.store', $serviceRequest) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Step Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g. Visa Application Submitted">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Milestone <span class="text-danger">*</span></label>
                            <select name="status_type" class="form-select" required>
                                @foreach(\App\Models\MilestoneType::allActive() as $mt)
                                    <option value="{{ $mt->key }}">{{ $mt->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="Describe this step…"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Scheduled Date</label>
                            <input type="date" name="scheduled_at" class="form-control">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_visible_to_client" id="vis_create" class="form-check-input" value="1" checked>
                                <label class="form-check-label" for="vis_create">Visible to client</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-plus-circle me-1"></i>Add to Timeline</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
function prefillService(id, name) {
    document.getElementById('serviceCatalogSelect').value = id;
    new bootstrap.Modal(document.getElementById('addServiceModal')).show();
}
function toggleReply(id) {
    const el = document.getElementById('reply-' + id);
    el.classList.toggle('d-none');
    if (!el.classList.contains('d-none')) el.querySelector('input,textarea').focus();
}
</script>
@endsection
