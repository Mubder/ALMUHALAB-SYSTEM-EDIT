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
    $canManageFields      = $user->hasPermission('edit_request');

    // Load field visibility rules once — used for all field checks below
    $serviceRequest->load('fieldVisibilities');
    $fieldVisMap = $serviceRequest->fieldVisibilityMap();
    $allRoles    = \App\Models\Role::orderBy('name')->get();
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
    $staffPermissions = [
        'transition_stage', 'force_transition', 'manage_assignments',
        'manage_followups', 'manage_services', 'manage_service_catalog',
        'manage_attachments', 'view_attachments', 'manage_users', 'view_audit_log',
    ];
    $employees = $canAssign
        ? \App\Models\User::whereHas('role', fn($q) =>
            $q->whereHas('permissions', fn($p) =>
                $p->whereIn('name', $staffPermissions)
            )
          )->orderBy('name')->get()
        : collect();
@endphp

@section('content')
<div class="row justify-content-center">
<div class="col-lg-11">

    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('service-requests.index') }}">{{ __('Requests') }}</a></li>
            <li class="breadcrumb-item active">
                {{ $serviceRequest->request_number ?? Str::limit($serviceRequest->title, 40) }}
            </li>
        </ol>
    </nav>

    {{-- ── Header ──────────────────────────────────────────── --}}
    <div class="page-card mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    @if($serviceRequest->is_rejected)
                        <span class="badge bg-danger px-3 py-2 fs-6">
                            <i class="bi bi-x-octagon me-1"></i>{{ __('Rejected') }}
                        </span>
                    @else
                        <span class="badge bg-{{ $currentCfg['color'] }} px-3 py-2 fs-6">
                            <i class="bi {{ $currentCfg['icon'] }} me-1"></i>{{ __($currentCfg['label']) }}
                        </span>
                        <span class="badge bg-light text-dark border">{{ __($stageStatus) }}</span>
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
                <div class="d-flex align-items-center gap-2 mt-2 mb-1 flex-wrap">
                    <h2 class="h4 fw-bold mb-0">{{ $serviceRequest->title }}</h2>
                    @if($serviceRequest->request_number)
                        <span class="badge bg-dark font-monospace fw-normal px-2 py-1" style="font-size:.8rem">
                            {{ $serviceRequest->request_number }}
                        </span>
                    @endif
                </div>
                <div class="text-muted small">
                    <i class="bi bi-calendar3 me-1"></i>{{ __('Submitted') }} {{ $serviceRequest->created_at->format('d M Y') }}
                    @if($canTransition)
                        &nbsp;·&nbsp;<i class="bi bi-person me-1"></i>{{ $serviceRequest->user->name }}
                    @endif
                    @if($canAudit)
                        &nbsp;·&nbsp;
                        <a href="{{ route('admin.audit-log.show', $serviceRequest) }}" class="text-muted">
                            <i class="bi bi-clock-history me-1"></i>{{ __('Audit Log') }}
                        </a>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @if($canEdit && !$serviceRequest->isClosed())
                    <a href="{{ route('service-requests.edit', $serviceRequest) }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                    </a>
                @endif
                @if($canDelete)
                    <form action="{{ route('service-requests.destroy', $serviceRequest) }}" method="POST"
                          onsubmit="return confirm('{{ __('Move to trash?') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i>{{ __('Delete') }}
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Stage Progress Bar ───────────────────────────────── --}}
    <div class="page-card mb-4 py-3 px-2">
        <div class="stage-progress">
            @foreach($stages as $num => $cfg)
                @php
                    $isPast     = $num < $currentStage;
                    $isCurrent  = $num === $currentStage;
                    $isRejected = $serviceRequest->is_rejected && $isCurrent;
                    $stateClass = $isPast ? 'past' : ($isCurrent ? 'current' : 'future');
                @endphp
                <div class="stage-step {{ $stateClass }} {{ $isRejected ? 'rejected' : '' }}">
                    <div class="stage-card">
                        <div class="stage-circle">
                            @if($isRejected)
                                <i class="bi bi-x-lg"></i>
                            @elseif($isPast)
                                <i class="bi bi-check-lg"></i>
                            @else
                                <i class="bi {{ $cfg['icon'] }}"></i>
                            @endif
                        </div>
                        <div class="stage-num">{{ $num }}</div>
                        <div class="stage-name">{{ __($cfg['label']) }}</div>
                        @if($isCurrent && !$serviceRequest->is_rejected)
                            <div class="stage-status-badge">{{ __($stageStatus) }}</div>
                        @endif
                    </div>
                    @if($num < count($stages))
                        <div class="stage-connector"></div>
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
                            {{ $serviceRequest->is_rejected ? __('Request Rejected') : __('Request Closed') }}
                        </h6>
                        <p class="text-muted small mb-0">
                            @if($serviceRequest->is_rejected)
                                {{ __('This request was rejected at the Client Approval stage. No further actions are available.') }}
                            @else
                                {{ __('This request has been closed and is now complete.') }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Workflow Controls ────────────────────────────────── --}}
            @php
                $isClosed = $serviceRequest->isClosed();
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
                        <strong>{{ $serviceRequest->is_rejected ? __('Rejected') : __('Closed') }} — {{ __('Admin Override Active') }}</strong><br>
                        {{ __('This request is') }} {{ $serviceRequest->is_rejected ? __('rejected') : __('closed') }}.
                        {{ __('You can update the status to un-reject it, or use') }} <strong>{{ __('Force Move') }}</strong> {{ __('to re-open it at any stage.') }}
                    </div>
                </div>
                @endif

                <h6 class="fw-bold mb-3 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                    <i class="bi bi-sliders me-1"></i>{{ __('Stage Controls') }} — {{ $currentCfg['label'] }}
                </h6>

                <div class="row g-3">

                    {{-- Update Status within Stage --}}
                    @if($canUpdateStatus && (!$isClosed || $canForce))
                    <div class="col-md-6">
                        <form action="{{ route('workflow.status', $serviceRequest) }}" method="POST">
                            @csrf
                            <label class="form-label small fw-600">{{ __('Update Stage Status') }}</label>
                            <div class="input-group input-group-sm">
                                <select name="stage_status" class="form-select">
                                    @foreach($currentCfg['statuses'] as $s)
                                        <option value="{{ $s }}" {{ $stageStatus === $s ? 'selected' : '' }}>{{ $s }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary px-3">{{ __('Apply') }}</button>
                            </div>
                            <input type="hidden" name="notes" value="">
                        </form>
                    </div>
                    @endif

                    {{-- Notes (only for transitions, only when not closed) --}}
                    @if($canTransition && !$isClosed)
                    <div class="col-md-6">
                        <label class="form-label small fw-600">{{ __('Notes for transition') }}</label>
                        <input type="text" id="transitionNotes" class="form-control form-control-sm"
                               placeholder="{{ __('Optional reason or note…') }}">
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
                                {{ __('Advance to') }} {{ $stages[$currentStage + 1]['label'] ?? __('Next') }}
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
                                {{ __('Return to') }} {{ $stages[$currentStage - 1]['label'] ?? __('Previous') }}
                            </button>
                        </form>
                    </div>
                    @endif

                    {{-- Force Transition — always visible for canForce, even on closed/rejected --}}
                    @if($canForce)
                    <div class="col-auto">
                        <button class="btn {{ $isClosed ? 'btn-danger' : 'btn-outline-dark' }} btn-sm"
                                data-bs-toggle="modal" data-bs-target="#forceModal">
                            <i class="bi bi-lightning me-1"></i>{{ $isClosed ? __('Force Reopen') : __('Force Move') }}
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
                        <label class="form-label small fw-600">{{ __('Assigned Employee') }}</label>
                        <select name="assigned_to" class="form-select form-select-sm">
                            <option value="">— {{ __('Unassigned') }} —</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ $serviceRequest->assigned_to == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-person-check me-1"></i>{{ __('Assign') }}
                    </button>
                </form>
                @endif
            </div>
            @endif

            {{-- Client Actions (stage 4: Client Approval) --}}
            @if($isClient && $currentStage === 4 && !$serviceRequest->is_rejected)
            <div class="page-card mb-4 border border-warning border-2">
                <h6 class="fw-bold mb-2 text-warning"><i class="bi bi-person-check me-1"></i>{{ __('Your Action Required') }}</h6>

                @if($stageStatus === 'Awaiting Payment')
                    <div class="d-flex align-items-center gap-2 p-3 rounded-2 mb-3"
                         style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25)">
                        <i class="bi bi-hourglass-split text-warning fs-5"></i>
                        <div>
                            <div class="fw-600 text-warning" style="font-size:.88rem">{{ __('Payment Receipt Submitted') }}</div>
                            <div class="text-muted small">{{ __('Our team is reviewing your payment. You will be notified once approved.') }}</div>
                        </div>
                    </div>
                @else
                    <p class="text-muted small mb-3">{{ __('Please review the prepared itinerary and confirm your payment by uploading a payment receipt.') }}</p>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#confirmPaymentModal">
                            <i class="bi bi-credit-card me-1"></i>{{ __('Confirm Payment') }}
                        </button>
                        <form action="{{ route('workflow.status', $serviceRequest) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="stage_status" value="Rejected">
                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                    onclick="return confirm('{{ __('Reject this request? This action stops the process.') }}')">
                                <i class="bi bi-x-circle me-1"></i>{{ __('Reject') }}
                            </button>
                        </form>
                    </div>
                @endif
            </div>

            {{-- Payment Confirmation Modal --}}
            <div class="modal fade" id="confirmPaymentModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <h6 class="modal-title fw-bold">
                                <i class="bi bi-credit-card text-success me-2"></i>{{ __('Confirm Payment') }}
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('workflow.confirm-payment', $serviceRequest) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <p class="text-muted small mb-3">
                                    {{ __('Upload a photo or PDF of your payment receipt. Our team will review it and confirm your payment.') }}
                                </p>
                                <div class="mb-3">
                                    <label class="form-label fw-600">{{ __('Payment Receipt') }} <span class="text-danger">*</span></label>
                                    <input type="file" name="receipt" class="form-control @error('receipt') is-invalid @enderror"
                                           accept=".jpg,.jpeg,.png,.pdf" required>
                                    <div class="form-text"><i class="bi bi-info-circle me-1"></i>{{ __('JPG, PNG or PDF — max 20 MB.') }}</div>
                                    @error('receipt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="p-2 rounded-2 small" style="background:#fffbeb;border:1px solid #fde68a;color:#92400e">
                                    <i class="bi bi-clock me-1"></i>{{ __('After submission, your payment status will remain pending until our team reviews the receipt.') }}
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn btn-success">
                                    <i class="bi bi-upload me-1"></i>{{ __('Submit Receipt') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            {{-- Staff: Approve Payment (stage 4, status = Awaiting Payment) --}}
            @if(!$isClient && $currentStage === 4 && $stageStatus === 'Awaiting Payment' && ($canTransition || $canUpdateStatus))
            <div class="page-card mb-4 border border-warning border-2">
                <h6 class="fw-bold mb-2 text-warning">
                    <i class="bi bi-receipt me-1"></i>{{ __('Payment Pending Review') }}
                </h6>
                <p class="text-muted small mb-3">
                    {{ __('The client has submitted a payment receipt. Please review the receipt in the Attachments section below, then approve or reject.') }}
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <form action="{{ route('workflow.approve-payment', $serviceRequest) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm"
                                onclick="return confirm('{{ __('Approve the payment and mark as Paid?') }}')">
                            <i class="bi bi-check-circle me-1"></i>{{ __('Approve Payment') }}
                        </button>
                    </form>
                    <form action="{{ route('workflow.status', $serviceRequest) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="stage_status" value="Rejected">
                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                onclick="return confirm('{{ __('Reject this payment?') }}')">
                            <i class="bi bi-x-circle me-1"></i>{{ __('Reject Payment') }}
                        </button>
                    </form>
                </div>
            </div>
            @endif

            {{-- ── Comments ──────────────────────────────────────── --}}
            <div class="page-card mb-4" id="comments">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h6 class="fw-bold mb-0 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                        <i class="bi bi-chat-left-text me-1"></i>{{ __('Comments') }}
                        <span class="badge bg-light text-dark border ms-1">{{ $comments->count() }}</span>
                    </h6>
                    @if($canSeeInternal)
                    <div class="d-flex gap-2">
                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.65rem">
                            <i class="bi bi-eye me-1"></i>{{ __('Client Visible') }}
                        </span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:.65rem">
                            <i class="bi bi-person-badge me-1"></i>{{ __('Staff Only') }}
                        </span>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.65rem">
                            <i class="bi bi-shield-lock me-1"></i>{{ __('Admin Only') }}
                        </span>
                    </div>
                    @endif
                </div>

                {{-- Add comment form --}}
                <form action="{{ route('stage-comments.store', $serviceRequest) }}" method="POST" class="mb-4">
                    @csrf
                    <div class="d-flex gap-2 align-items-start">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                             style="width:2rem;height:2rem;font-size:.78rem">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="flex-grow-1">
                            @if($canSeeInternal)
                            {{-- Tab switcher for staff --}}
                            <div class="mb-2" id="comment-type-tabs">
                                <div class="btn-group btn-group-sm" role="group">
                                    <input type="radio" class="btn-check" name="visibility" id="vis-all" value="all" checked>
                                    <label class="btn btn-outline-success" for="vis-all">
                                        <i class="bi bi-eye me-1"></i>{{ __('Client Visible') }}
                                    </label>
                                    <input type="radio" class="btn-check" name="visibility" id="vis-employee" value="employee">
                                    <label class="btn btn-outline-primary" for="vis-employee">
                                        <i class="bi bi-person-badge me-1"></i>{{ __('Staff Only') }}
                                    </label>
                                    @if($user->hasPermission('manage_users'))
                                    <input type="radio" class="btn-check" name="visibility" id="vis-admin" value="admin">
                                    <label class="btn btn-outline-danger" for="vis-admin">
                                        <i class="bi bi-shield-lock me-1"></i>{{ __('Admin Only') }}
                                    </label>
                                    @endif
                                </div>
                            </div>
                            @else
                            <input type="hidden" name="visibility" value="all">
                            @endif
                            <textarea name="content" rows="2"
                                      class="form-control form-control-sm mb-2"
                                      id="comment-textarea"
                                      placeholder="{{ __('Add a comment…') }}" required></textarea>
                            <button type="submit" class="btn btn-primary btn-sm px-3">
                                <i class="bi bi-send me-1"></i>{{ __('Post') }}
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Comments list --}}
                @forelse($comments as $comment)
                    @php
                        $vcfg     = $comment->visibilityConfig();
                        $isPublic = $comment->visibility === 'all';
                        $bgStyle  = $isPublic
                            ? 'background:rgba(22,163,74,.04);border:1px solid rgba(22,163,74,.15)'
                            : ($comment->visibility === 'employee'
                                ? 'background:rgba(37,99,235,.04);border:1px solid rgba(37,99,235,.15)'
                                : 'background:rgba(220,53,69,.04);border:1px solid rgba(220,53,69,.15)');
                        $avatarBg = $isPublic ? '#16a34a' : ($comment->visibility === 'employee' ? '#2563eb' : '#dc2626');
                    @endphp
                    <div class="comment-thread mb-3">
                        <div class="d-flex gap-2 align-items-start">
                            <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                 style="width:2rem;height:2rem;font-size:.78rem;background:{{ $avatarBg }}">
                                {{ strtoupper(substr($comment->creator->name, 0, 1)) }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="rounded-3 p-3" style="{{ $bgStyle }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-1">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-600 small">{{ $comment->creator->name }}</span>
                                            <span class="badge bg-{{ $vcfg['color'] }}-subtle text-{{ $vcfg['color'] }} border border-{{ $vcfg['color'] }}-subtle"
                                                  style="font-size:.62rem">
                                                <i class="bi {{ $vcfg['icon'] }} me-1"></i>{{ __($vcfg['label']) }}
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-muted" style="font-size:.72rem">
                                                {{ $comment->created_at->diffForHumans() }}
                                            </span>
                                            @if($comment->created_by === $user->id || $user->hasPermission('manage_users'))
                                            <form action="{{ route('stage-comments.destroy', [$serviceRequest, $comment]) }}"
                                                  method="POST" onsubmit="return confirm('{{ __('Remove comment?') }}')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-link btn-sm text-danger p-0" style="line-height:1">
                                                    <i class="bi bi-trash" style="font-size:.75rem"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="small" style="line-height:1.65;white-space:pre-wrap">{{ $comment->content }}</div>
                                </div>

                                {{-- Reply button --}}
                                <div class="mt-1 ms-1">
                                    <a href="#" class="text-muted small text-decoration-none"
                                       onclick="toggleReply({{ $comment->id }}); return false;">
                                        <i class="bi bi-reply me-1"></i>{{ __('Reply') }}
                                    </a>
                                    <div class="d-none mt-2" id="reply-{{ $comment->id }}">
                                        <form action="{{ route('stage-comments.store', $serviceRequest) }}" method="POST"
                                              class="d-flex gap-2">
                                            @csrf
                                            <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                            <input type="hidden" name="visibility" value="{{ $comment->visibility }}">
                                            <input type="text" name="content" class="form-control form-control-sm"
                                                   placeholder="{{ __('Write a reply…') }}" required>
                                            <button type="submit" class="btn btn-outline-primary btn-sm flex-shrink-0">{{ __('Send') }}</button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Replies --}}
                                @foreach($comment->replies->filter(fn($r) => $r->isVisibleTo($user)) as $reply)
                                <div class="d-flex gap-2 align-items-start mt-2 ms-4">
                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                         style="width:1.75rem;height:1.75rem;font-size:.7rem">
                                        {{ strtoupper(substr($reply->creator->name, 0, 1)) }}
                                    </div>
                                    <div class="rounded-3 p-2 flex-grow-1" style="{{ $bgStyle }};opacity:.85">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-600 small">{{ $reply->creator->name }}</span>
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
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-chat-left d-block mb-2" style="opacity:.25;font-size:1.75rem"></i>
                        {{ __('No comments yet. Be the first to add one.') }}
                    </div>
                @endforelse
            </div>

            {{-- ── Stage Attachments ──────────────────────────────── --}}
            @if($canManageAttachments || $stageAttachments->count())
            <div class="page-card mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
                        <i class="bi bi-paperclip me-1"></i>{{ __('Stage Attachments') }}
                    </h6>
                    @if($canManageAttachments)
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadAttachmentModal">
                        <i class="bi bi-upload me-1"></i>{{ __('Upload') }}
                    </button>
                    @endif
                </div>

                @if($stageAttachments->isEmpty())
                    <div class="text-center py-3 text-muted small">
                        <i class="bi bi-paperclip d-block mb-1" style="opacity:.3;font-size:1.5rem"></i>
                        {{ __('No attachments yet.') }}
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
                                               class="btn btn-outline-secondary btn-sm btn-action" title="{{ __('Download') }}">
                                                <i class="bi bi-download"></i>
                                            </a>
                                            @if($canManageAttachments)
                                            <form action="{{ route('stage-attachments.destroy', [$serviceRequest, $att]) }}"
                                                  method="POST" onsubmit="return confirm('{{ __('Remove this file?') }}')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm btn-action" title="{{ __('Delete') }}">
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
                            <h6 class="modal-title fw-bold"><i class="bi bi-upload text-primary me-2"></i>{{ __('Upload Attachments') }}</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('stage-attachments.store', $serviceRequest) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">{{ __('Files') }} <span class="text-danger">*</span></label>
                                        <input type="file" name="files[]" class="form-control" multiple required
                                               accept=".pdf,.jpg,.jpeg,.png,.gif,.doc,.docx,.xls,.xlsx,.zip,.txt">
                                        <div class="form-text">{{ __('PDF, images, Word, Excel, ZIP — max 20 MB each.') }}</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">{{ __('Stage') }}</label>
                                        <select name="stage" class="form-select form-select-sm">
                                            @foreach(\App\Services\WorkflowService::STAGES as $n => $cfg)
                                                <option value="{{ $n }}" {{ $n === $currentStage ? 'selected' : '' }}>
                                                    {{ $n }}. {{ $cfg['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    {{-- Visibility is set automatically by the system --}}
                                    <input type="hidden" name="visibility" value="employee">
                                </div>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-upload me-1"></i>{{ __('Upload') }}
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
                        <i class="bi bi-diagram-3 me-1"></i>{{ __('Journey Timeline') }}
                    </h6>
                    @if($canManage)
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFollowUpModal">
                            <i class="bi bi-plus-lg me-1"></i>{{ __('Add Step') }}
                        </button>
                    @endif
                </div>

                @if($allFollowUps->isEmpty())
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-diagram-3 d-block mb-2" style="font-size:2rem;opacity:.2"></i>
                        <p class="small mb-2">{{ __('No steps yet.') }}</p>
                        @if($canManage)
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addFollowUpModal">
                            <i class="bi bi-plus-lg me-1"></i>{{ __('Add First Step') }}
                        </button>
                        @else
                        <p class="small text-muted">{{ __('Your request is being reviewed. Updates will appear here.') }}</p>
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
                                                        <i class="bi bi-eye-slash me-1"></i>{{ __('Private') }}
                                                    </span>
                                                @endif
                                                @if($isCurrent)
                                                    <span class="badge bg-primary" style="font-size:.7rem">{{ __('Current') }}</span>
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
                                                        {{ $fu->is_completed ? __('Reopen') : __('Mark Complete') }}
                                                    </button>
                                                </form>
                                                <a href="{{ route('follow-ups.edit', [$serviceRequest, $fu]) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil me-1"></i>{{ __('Edit') }}
                                                </a>
                                                <form action="{{ route('follow-ups.destroy', [$serviceRequest, $fu]) }}" method="POST"
                                                      onsubmit="return confirm('{{ __('Remove this step?') }}')">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </form>
                                                <span class="text-muted ms-auto" style="font-size:.7rem">{{ __('by') }} {{ $fu->creator->name }}</span>
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
                    <i class="bi bi-broadcast me-1"></i>{{ __('Current Stage') }}
                </h6>
                <div class="d-flex align-items-center gap-3 p-3 rounded-3 bg-light border mb-3">
                    <div class="text-{{ $currentCfg['color'] }}" style="font-size:1.75rem">
                        <i class="bi {{ $currentCfg['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="fw-bold">{{ __($currentCfg['label']) }}</div>
                        <div class="small text-muted">{{ __($stageStatus) }}</div>
                        @if($serviceRequest->stage_entered_at)
                        <div class="text-muted" style="font-size:.72rem">
                            <i class="bi bi-clock me-1"></i>{{ $serviceRequest->stageDaysElapsed() }} {{ __('day(s) in this stage') }}
                        </div>
                        @endif
                    </div>
                </div>
                @if(!$serviceRequest->isAtFinalStage() && !$serviceRequest->isClosed())
                    <div class="text-muted small">
                        <i class="bi bi-arrow-right me-1"></i>{{ __('Next') }}:
                        <strong>{{ $stages[$currentStage + 1]['label'] ?? '—' }}</strong>
                    </div>
                @elseif($serviceRequest->stage_status === 'Closed')
                    <div class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>{{ __('Request Closed') }}</div>
                @endif

                {{-- Stage history (collapsible) --}}
                @if($canTransition)
                <div class="mt-3">
                    <a href="#stageHistory" class="small text-muted text-decoration-none" data-bs-toggle="collapse">
                        <i class="bi bi-clock-history me-1"></i>{{ __('Stage History') }}
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
                    <i class="bi bi-arrow-right-circle me-1"></i>{{ __('Coming Up') }}
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
                        <i class="bi bi-file-text me-1 text-muted"></i>{{ __('Request Details') }}
                    </span>
                    <i class="bi bi-chevron-down text-muted small"></i>
                </a>
                <div class="collapse show mt-3" id="requestDetails">

                    {{-- Client Info --}}
                    @if($serviceRequest->client_name || $serviceRequest->client_phone || $serviceRequest->client_email || $serviceRequest->client_country)
                        @if($serviceRequest->isFieldVisibleTo('client_info', $user, $fieldVisMap))
                        <div class="border rounded-3 p-2 mb-3 bg-light">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="text-muted" style="font-size:.7rem;text-transform:uppercase">
                                    <i class="bi bi-person-fill me-1"></i>{{ __('Client Information') }}
                                </div>
                                @include('service_requests._field_visibility_toggle', ['field' => 'client_info', 'sr' => $serviceRequest, 'map' => $fieldVisMap])
                            </div>
                            <div class="row g-1 small">
                                @if($serviceRequest->client_name)
                                <div class="col-12">
                                    <span class="text-muted">{{ __('Name') }}:</span>
                                    <strong>{{ $serviceRequest->client_name }}</strong>
                                </div>
                                @endif
                                @if($serviceRequest->client_phone)
                                <div class="col-12">
                                    <span class="text-muted">{{ __('Phone') }}:</span>
                                    {{ $serviceRequest->client_phone_code }} {{ $serviceRequest->client_phone }}
                                </div>
                                @endif
                                @if($serviceRequest->client_email)
                                <div class="col-12">
                                    <span class="text-muted">{{ __('Email') }}:</span>
                                    <a href="mailto:{{ $serviceRequest->client_email }}" class="text-decoration-none">
                                        {{ $serviceRequest->client_email }}
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                        @elseif($canManageFields)
                        @include('service_requests._field_hidden_row', ['field' => 'client_info', 'label' => __('Client Information'), 'sr' => $serviceRequest, 'map' => $fieldVisMap])
                        @endif
                    @endif

                    {{-- Service Type + Status (never hidden) --}}
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <div class="text-muted" style="font-size:.7rem;text-transform:uppercase">{{ __('Service Type') }}</div>
                            <div class="small fw-500">{{ $serviceRequest->serviceType->name }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted" style="font-size:.7rem;text-transform:uppercase">{{ __('Request Status') }}</div>
                            <span class="badge {{ $badgeClass }} small">{{ __($badgeLabel) }}</span>
                        </div>
                    </div>

                    {{-- Description --}}
                    @if($serviceRequest->isFieldVisibleTo('description', $user, $fieldVisMap))
                    <div class="mb-2 position-relative">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <div class="text-muted" style="font-size:.7rem;text-transform:uppercase">{{ __('Description') }}</div>
                            @include('service_requests._field_visibility_toggle', ['field' => 'description', 'sr' => $serviceRequest, 'map' => $fieldVisMap])
                        </div>
                        <div class="bg-light rounded-3 p-2 small" style="white-space:pre-wrap;line-height:1.6">{{ $serviceRequest->description }}</div>
                    </div>
                    @elseif($canManageFields)
                    @include('service_requests._field_hidden_row', ['field' => 'description', 'label' => __('Description'), 'sr' => $serviceRequest, 'map' => $fieldVisMap])
                    @endif

                    {{-- Travel --}}
                    @if($serviceRequest->client_country || $serviceRequest->destination_country || $serviceRequest->travel_date_start || $serviceRequest->travel_date_end)
                        @if($serviceRequest->isFieldVisibleTo('travel_info', $user, $fieldVisMap))
                        <div class="border-top pt-2 mb-2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="text-muted" style="font-size:.7rem;text-transform:uppercase">
                                    <i class="bi bi-airplane me-1"></i>{{ __('Travel') }}
                                </div>
                                @include('service_requests._field_visibility_toggle', ['field' => 'travel_info', 'sr' => $serviceRequest, 'map' => $fieldVisMap])
                            </div>
                            <div class="row g-1 small">
                                @if($serviceRequest->client_country)
                                <div class="col-12">
                                    <span class="text-muted">{{ __('From') }}:</span> {{ $serviceRequest->client_country }}
                                </div>
                                @endif
                                @if($serviceRequest->destination_country)
                                <div class="col-12">
                                    <span class="text-muted">{{ __('To') }}:</span>
                                    {{ $serviceRequest->destination_country }}{{ $serviceRequest->destination_city ? ', '.$serviceRequest->destination_city : '' }}
                                </div>
                                @endif
                                @if($serviceRequest->travel_date_start)
                                <div class="col-6"><span class="text-muted">{{ __('Depart') }}:</span> {{ $serviceRequest->travel_date_start->format('d M Y') }}</div>
                                @endif
                                @if($serviceRequest->travel_date_end)
                                <div class="col-6"><span class="text-muted">{{ __('Return') }}:</span> {{ $serviceRequest->travel_date_end->format('d M Y') }}</div>
                                @endif
                                @if($serviceRequest->durationDays())
                                <div class="col-12">
                                    <span class="text-muted">{{ __('Duration') }}:</span> <strong>{{ $serviceRequest->durationDays() }} {{ __('days') }}</strong>
                                </div>
                                @endif
                            </div>
                        </div>
                        @elseif($canManageFields)
                        @include('service_requests._field_hidden_row', ['field' => 'travel_info', 'label' => __('Travel Information'), 'sr' => $serviceRequest, 'map' => $fieldVisMap])
                        @endif
                    @endif
                    @php
                        $visibleAttachments = $serviceRequest->attachments->filter(
                            fn($a) => $a->isVisibleTo($user)
                        );
                    @endphp
                    @if($visibleAttachments->count())
                    <div class="border-top pt-2">
                        <div class="text-muted mb-1" style="font-size:.7rem;text-transform:uppercase">
                            <i class="bi bi-paperclip me-1"></i>{{ __('Attachments') }}
                        </div>
                        @foreach($visibleAttachments as $att)
                        @php $vcfg = $att->visibilityConfig(); @endphp
                        <div class="p-2 bg-light rounded mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark text-primary small flex-shrink-0"></i>
                                <span class="small text-truncate flex-grow-1">{{ $att->original_name }}</span>
                                <span class="badge bg-{{ $vcfg['color'] }}-subtle text-{{ $vcfg['color'] }} border border-{{ $vcfg['color'] }}-subtle"
                                      style="font-size:.65rem" title="{{ $att->required_permission }}">
                                    <i class="bi {{ $vcfg['icon'] }} me-1"></i>{{ __($vcfg['label']) }}
                                </span>
                                <a href="{{ $att->downloadUrl() }}"
                                   class="btn btn-outline-primary btn-sm btn-action py-0 flex-shrink-0"
                                   @if($att->visibility === 'public') target="_blank" @endif>
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                            @if($canManageAttachments)
                            @php
                                $attSelected = ($att->visibility === 'admin' && $att->required_permission)
                                    ? $att->required_permission
                                    : 'all';
                            @endphp
                            <form action="{{ route('service-requests.attachments.visibility', [$serviceRequest, $att]) }}"
                                  method="POST" class="d-flex gap-1 mt-1 align-items-center">
                                @csrf @method('PATCH')
                                <select name="visibility" class="form-select form-select-sm py-0"
                                        style="font-size:.72rem;height:1.6rem">
                                    <option value="all" {{ $attSelected === 'all' ? 'selected' : '' }}>{{ __('Everyone') }}</option>
                                    @foreach($allRoles as $role)
                                        <option value="{{ $role->name }}" {{ $attSelected === $role->name ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-secondary btn-sm py-0"
                                        style="font-size:.72rem;height:1.6rem">{{ __('Save') }}</button>
                            </form>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif

                    {{-- Companions --}}
                    @if(($serviceRequest->companions_count ?? 0) > 0)
                    <div class="border-top pt-2 mt-2">
                        <div class="text-muted mb-2" style="font-size:.7rem;text-transform:uppercase">
                            <i class="bi bi-people-fill me-1"></i>
                            {{ __('Companions') }} ({{ $serviceRequest->companions_count }})
                        </div>
                        @if($serviceRequest->companions_data && count($serviceRequest->companions_data))
                            @foreach($serviceRequest->companions_data as $i => $companion)
                            <div class="d-flex align-items-start gap-2 p-2 bg-light rounded mb-1 small">
                                <span class="badge bg-secondary-subtle text-secondary border flex-shrink-0">{{ $i + 1 }}</span>
                                <div>
                                    <div class="fw-500">{{ $companion['name'] ?? '—' }}</div>
                                    @if(!empty($companion['phone']))
                                    <div class="text-muted">
                                        {{ $companion['phone_code'] ?? '' }} {{ $companion['phone'] }}
                                    </div>
                                    @endif
                                    @if(!empty($companion['email']))
                                    <div class="text-muted">{{ $companion['email'] }}</div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-muted small fst-italic">{{ __('No companion details provided.') }}</div>
                        @endif
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
                <i class="bi bi-grid-3x3-gap me-1"></i>{{ __('Arranged Services') }}
            </h6>
            @if($canManageServices)
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('Add Service') }}
                </button>
            @endif
        </div>

        @if($canManageServices && $suggestedServices->count())
        <div class="alert alert-info d-flex align-items-start gap-2 py-2 mb-3" style="font-size:.85rem">
            <i class="bi bi-lightbulb-fill mt-1 flex-shrink-0"></i>
            <div>
                <strong>{{ __('Suggested for current stage:') }}</strong>
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
                                      method="POST" onsubmit="return confirm('{{ __('Remove?') }}')">
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
                                <h6 class="modal-title fw-bold"><i class="bi {{ $rs->service->icon ?? 'bi-star' }} me-2"></i>{{ __('Edit') }}: {{ $rs->service->name }}</h6>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form action="{{ route('request-services.update', [$serviceRequest, $rs]) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">{{ __('Status') }}</label>
                                            <select name="status" class="form-select form-select-sm">
                                                @foreach(\App\Models\RequestService::STATUSES as $k => $cfg)
                                                    <option value="{{ $k }}" {{ $rs->status === $k ? 'selected':'' }}>{{ $cfg['label'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">{{ __('Scheduled Date') }}</label>
                                            <input type="date" name="scheduled_at" class="form-control form-control-sm" value="{{ $rs->scheduled_at?->format('Y-m-d') }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">{{ __('Reference') }}</label>
                                            <input type="text" name="reference" class="form-control form-control-sm" value="{{ $rs->reference }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">{{ __('Notes') }}</label>
                                            <textarea name="notes" rows="2" class="form-control form-control-sm">{{ $rs->notes }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-circle me-1"></i>{{ __('Save') }}</button>
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
                <p class="small mb-2">{{ __('No services added yet.') }}</p>
                <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('Add First Service') }}
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
                <h5 class="modal-title fw-bold"><i class="bi bi-lightning text-warning me-2"></i>{{ __('Force Stage Transition') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('workflow.force', $serviceRequest) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning small"><i class="bi bi-exclamation-triangle me-1"></i>{{ __('This bypasses the normal workflow order. Use with caution.') }}</div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Move to Stage') }}</label>
                        <select name="to_stage" class="form-select">
                            @foreach($stages as $n => $cfg)
                                <option value="{{ $n }}" {{ $n === $currentStage ? 'disabled selected' : '' }}>
                                    {{ $n }}. {{ $cfg['label'] }} {{ $n === $currentStage ? '('.__('current').')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('Reason') }} <span class="text-danger">*</span></label>
                        <textarea name="notes" rows="2" class="form-control" placeholder="{{ __('Required: explain why you are overriding…') }}" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-warning px-4"><i class="bi bi-lightning me-1"></i>{{ __('Force Move') }}</button>
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
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-success me-2"></i>{{ __('Add Service') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('request-services.store', $serviceRequest) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">{{ __('Service') }} <span class="text-danger">*</span></label>
                            <select name="service_catalog_id" id="serviceCatalogSelect" class="form-select" required>
                                <option value="">— {{ __('Select a service') }} —</option>
                                @foreach($allCatalogServices as $svc)
                                    <option value="{{ $svc->id }}">{{ $svc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" class="form-select">
                                @foreach(\App\Models\RequestService::STATUSES as $k => $cfg)
                                    <option value="{{ $k }}" {{ $k === 'pending' ? 'selected':'' }}>{{ $cfg['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label">{{ __('Scheduled Date') }}</label><input type="date" name="scheduled_at" class="form-control"></div>
                        <div class="col-12"><label class="form-label">{{ __('Reference / Booking No.') }}</label><input type="text" name="reference" class="form-control" placeholder="e.g. HTL-4521"></div>
                        <div class="col-12"><label class="form-label">{{ __('Notes') }}</label><textarea name="notes" rows="2" class="form-control" placeholder="{{ __('Any details…') }}"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success px-4"><i class="bi bi-plus-circle me-1"></i>{{ __('Add') }}</button>
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
                <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>{{ __('Add Timeline Step') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('follow-ups.store', $serviceRequest) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">{{ __('Step Title') }} <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required placeholder="{{ __('e.g. Visa Application Submitted') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Milestone') }} <span class="text-danger">*</span></label>
                            <select name="status_type" class="form-select" required>
                                @foreach(\App\Models\MilestoneType::allActive() as $mt)
                                    <option value="{{ $mt->key }}">{{ $mt->label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Description') }}</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="{{ __('Describe this step…') }}"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Scheduled Date') }}</label>
                            <input type="date" name="scheduled_at" class="form-control">
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" name="is_visible_to_client" id="vis_create" class="form-check-input" value="1" checked>
                                <label class="form-check-label" for="vis_create">{{ __('Visible to client') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-plus-circle me-1"></i>{{ __('Add to Timeline') }}</button>
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

// Comment type → textarea border color
(function () {
    const radios   = document.querySelectorAll('input[name="visibility"]');
    const textarea = document.getElementById('comment-textarea');
    if (!radios.length || !textarea) return;

    const colors = { all: '#16a34a', employee: '#2563eb', admin: '#dc2626' };
    function update() {
        const val = document.querySelector('input[name="visibility"]:checked')?.value || 'all';
        textarea.style.borderColor = colors[val] || '';
        textarea.style.boxShadow   = `0 0 0 3px ${colors[val]}22` || '';
    }
    radios.forEach(r => r.addEventListener('change', update));
    update();
})();
</script>
@endsection
