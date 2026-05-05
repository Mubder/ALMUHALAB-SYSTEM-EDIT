@extends('layouts.app')

@section('title', 'Roles & Permissions')

@php
/*
 * Permission metadata: label, icon, color, group, risk, description, detail
 * risk: null | 'medium' | 'high'
 * group: used to cluster checkboxes under category headings
 */
$permissionMeta = [

    /* ── Requests ─────────────────────────────────────── */
    'create_request' => [
        'label'  => 'Create Request',
        'icon'   => 'bi-plus-circle',
        'color'  => 'success',
        'group'  => 'Requests',
        'risk'   => null,
        'desc'   => 'Submit new service requests.',
        'detail' => 'Users with this permission can open the "New Request" form and submit requests into the system. Without it, the button is hidden.',
    ],
    'view_request' => [
        'label'  => 'View Requests',
        'icon'   => 'bi-eye',
        'color'  => 'info',
        'group'  => 'Requests',
        'risk'   => null,
        'desc'   => 'Read the list and details of service requests.',
        'detail' => 'Regular users see only their own requests. Employees and Admins see all requests. Without this, the Requests page returns a 403.',
    ],
    'edit_request' => [
        'label'  => 'Edit Requests',
        'icon'   => 'bi-pencil',
        'color'  => 'warning',
        'group'  => 'Requests',
        'risk'   => null,
        'desc'   => 'Modify the title, description, and attachments of any request.',
        'detail' => 'Allows opening the Edit form and saving changes. Typically granted to Admin and senior employees, not to regular clients.',
    ],
    'delete_request' => [
        'label'  => 'Delete Request',
        'icon'   => 'bi-trash',
        'color'  => 'danger',
        'group'  => 'Requests',
        'risk'   => 'medium',
        'desc'   => 'Move a request to the Trash (soft-delete).',
        'detail' => 'The request is not permanently removed — it is hidden from normal views but can be restored. No data is lost.',
    ],
    'update_status' => [
        'label'  => 'Update Stage Status',
        'icon'   => 'bi-arrow-repeat',
        'color'  => 'primary',
        'group'  => 'Requests',
        'risk'   => null,
        'desc'   => 'Change the status label within the current workflow stage.',
        'detail' => 'Each stage has its own set of statuses (e.g. Pending → In Progress → Completed). This permission allows setting those labels. Clients cannot use this — they can only take specific actions in Stage 4.',
    ],

    /* ── Workflow ──────────────────────────────────────── */
    'transition_stage' => [
        'label'  => 'Advance / Return Stage',
        'icon'   => 'bi-arrow-right-circle',
        'color'  => 'primary',
        'group'  => 'Workflow',
        'risk'   => 'medium',
        'desc'   => 'Move a request forward or backward through the 7-stage workflow.',
        'detail' => 'Allows clicking the "Advance" and "Return" buttons on a request. Each transition is logged in the stage history. Grant to employees and admins who process requests.',
    ],
    'force_transition' => [
        'label'  => 'Force Transition',
        'icon'   => 'bi-skip-forward-circle',
        'color'  => 'danger',
        'group'  => 'Workflow',
        'risk'   => 'high',
        'desc'   => 'Jump a request to any stage, skipping the normal order.',
        'detail' => 'Bypasses the sequential stage rules entirely and moves the request directly to any chosen stage. Use only for corrections or emergencies. Grant exclusively to Administrators.',
    ],
    'manage_assignments' => [
        'label'  => 'Assign Employee',
        'icon'   => 'bi-person-check',
        'color'  => 'info',
        'group'  => 'Workflow',
        'risk'   => null,
        'desc'   => 'Assign or re-assign an employee as the owner of a request.',
        'detail' => 'Shows the "Assign To" control on the request detail page. The assigned employee receives a notification and appears in the request header. Typically granted to managers and admins.',
    ],

    /* ── Comments ──────────────────────────────────────── */
    'view_all_comments' => [
        'label'  => 'View All Comments',
        'icon'   => 'bi-chat-square-text',
        'color'  => 'secondary',
        'group'  => 'Comments',
        'risk'   => null,
        'desc'   => 'See internal (employee-only and admin-only) comments.',
        'detail' => 'Without this, users only see public comments (visible to all). With it, all visibility levels — including "Employee Only" and "Admin Only" — are shown. Grant to staff members.',
    ],

    /* ── Follow-ups ────────────────────────────────────── */
    'manage_followups' => [
        'label'  => 'Manage Follow-ups',
        'icon'   => 'bi-calendar-check',
        'color'  => 'info',
        'group'  => 'Follow-ups',
        'risk'   => null,
        'desc'   => 'Create, edit, complete, and delete follow-up reminders on requests.',
        'detail' => 'Follow-ups are scheduled tasks attached to a request (e.g. "Call client on Thursday"). Employees use these to track action items. Without this, the Follow-ups section is hidden.',
    ],

    /* ── Services ──────────────────────────────────────── */
    'manage_services' => [
        'label'  => 'Manage Request Services',
        'icon'   => 'bi-grid',
        'color'  => 'primary',
        'group'  => 'Services',
        'risk'   => null,
        'desc'   => 'Add, edit, and remove services attached to a specific request.',
        'detail' => 'Each request can have a list of arranged services (e.g. flights, hotels). This permission controls the "Add Service" form on the request detail page. Clients never see this.',
    ],
    'manage_service_catalog' => [
        'label'  => 'Manage Service Catalog',
        'icon'   => 'bi-grid-3x3-gap',
        'color'  => 'primary',
        'group'  => 'Services',
        'risk'   => null,
        'desc'   => 'Create and manage the master list of available services.',
        'detail' => 'Controls access to Admin → Service Catalog. Services defined here are the options employees pick from when adding services to a request. Grant to admins responsible for the catalog.',
    ],
    'manage_attachments' => [
        'label'  => 'Manage Stage Attachments',
        'icon'   => 'bi-paperclip',
        'color'  => 'primary',
        'group'  => 'Services',
        'risk'   => 'medium',
        'desc'   => 'Upload, delete, and set visibility for files attached to workflow stages.',
        'detail' => 'Employees with this permission see the "Upload" button on the request detail page. They can attach PDFs, images, and documents to any stage and control who can see each file (Admin Only / Internal / Shared with Client). Also grants full read access to all attachments.',
    ],
    'view_attachments' => [
        'label'  => 'View Internal Attachments',
        'icon'   => 'bi-eye',
        'color'  => 'info',
        'group'  => 'Services',
        'risk'   => null,
        'desc'   => 'View internal (employee-level) and client-facing attachments on requests.',
        'detail' => 'Read-only access to stage attachments. Employees can download files but cannot upload or delete. Admin-only files remain hidden. Grant to employees who need to view documents without the ability to modify them.',
    ],

    /* ── Trash ─────────────────────────────────────────── */
    'view_trash' => [
        'label'  => 'View Trash',
        'icon'   => 'bi-trash2',
        'color'  => 'secondary',
        'group'  => 'Trash',
        'risk'   => null,
        'desc'   => 'Access the Trash page and browse soft-deleted requests.',
        'detail' => 'Shows the "Trash" link in the navigation. Users without this see a 403 if they try to visit the URL directly.',
    ],
    'restore_request' => [
        'label'  => 'Restore Request',
        'icon'   => 'bi-arrow-counterclockwise',
        'color'  => 'success',
        'group'  => 'Trash',
        'risk'   => null,
        'desc'   => 'Recover a soft-deleted request back to the active list.',
        'detail' => 'Moves a trashed request back to normal status, making it visible and editable again. No data is lost — this is a safe operation.',
    ],
    'force_delete_request' => [
        'label'  => 'Permanently Delete',
        'icon'   => 'bi-x-octagon',
        'color'  => 'danger',
        'group'  => 'Trash',
        'risk'   => 'high',
        'desc'   => 'Permanently erase a request and all its files from the system.',
        'detail' => 'This is irreversible. The database row, all attachments, comments, follow-ups, and stage history for that request will be deleted forever. Grant only to super-admins.',
    ],

    /* ── Administration ────────────────────────────────── */
    'manage_users' => [
        'label'  => 'Manage Users & Roles',
        'icon'   => 'bi-people',
        'color'  => 'dark',
        'group'  => 'Administration',
        'risk'   => 'high',
        'desc'   => 'Access the Admin Panel: manage users, roles, and permissions.',
        'detail' => 'Unlocks the entire Admin section including Users, Roles & Permissions, Service Types, and Audit Log. A user with this permission can grant themselves any other permission. Grant only to trusted administrators.',
    ],
    'view_audit_log' => [
        'label'  => 'View Audit Log',
        'icon'   => 'bi-clock-history',
        'color'  => 'secondary',
        'group'  => 'Administration',
        'risk'   => null,
        'desc'   => 'Read the system-wide activity log of all actions.',
        'detail' => 'The audit log shows every action taken: who created, edited, transitioned, or deleted requests and when. Useful for accountability and debugging. Read-only — it cannot be edited.',
    ],
];

/* Group definitions in display order */
$groups = [
    'Requests'       => ['icon' => 'bi-folder2-open',    'color' => 'primary'],
    'Workflow'       => ['icon' => 'bi-diagram-3',       'color' => 'info'],
    'Comments'       => ['icon' => 'bi-chat-square-dots','color' => 'secondary'],
    'Follow-ups'     => ['icon' => 'bi-calendar-check',  'color' => 'info'],
    'Services'       => ['icon' => 'bi-grid',            'color' => 'primary'],
    'Trash'          => ['icon' => 'bi-trash2',          'color' => 'danger'],
    'Administration' => ['icon' => 'bi-shield-lock',     'color' => 'dark'],
];
@endphp

@section('content')

{{-- ── Page header ──────────────────────────────────────────── --}}
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
    <div>
        <h1 class="h4 fw-700 mb-1">
            <i class="bi bi-shield-lock text-primary me-2"></i>Roles & Permissions
        </h1>
        <p class="text-muted mb-0" style="font-size:.875rem">
            Create roles, then choose exactly what each role is allowed to do.
        </p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-people me-1"></i>Manage Users
    </a>
</div>

{{-- ── Permission Reference ──────────────────────────────────── --}}
<div class="page-card mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h6 class="fw-700 mb-0" style="font-size:.9rem">
                <i class="bi bi-journal-text text-primary me-2"></i>Permission Reference
            </h6>
            <p class="text-muted mb-0 mt-1" style="font-size:.8rem">
                Every permission in the system, what it does, and how risky it is.
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size:.68rem">
                <i class="bi bi-exclamation-triangle me-1"></i>Medium risk
            </span>
            <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle" style="font-size:.68rem">
                <i class="bi bi-x-octagon me-1"></i>High risk — use with care
            </span>
        </div>
    </div>

    @foreach($groups as $groupName => $groupMeta)
        @php
            $groupPerms = collect($permissionMeta)->filter(fn($m) => $m['group'] === $groupName);
        @endphp
        @if($groupPerms->isNotEmpty())
        <div class="mb-4">
            <div class="d-flex align-items-center gap-2 mb-2 pb-1" style="border-bottom:1px solid var(--color-border)">
                <i class="bi {{ $groupMeta['icon'] }} text-{{ $groupMeta['color'] }}" style="font-size:.95rem"></i>
                <span class="fw-700 text-uppercase" style="font-size:.7rem;letter-spacing:.07em;color:var(--color-muted)">
                    {{ $groupName }}
                </span>
            </div>
            <div class="row g-2">
                @foreach($groupPerms as $pName => $meta)
                <div class="col-md-6 col-xl-4">
                    <div class="h-100 rounded-3 p-3 d-flex gap-3
                        @if($meta['risk'] === 'high')   border border-danger bg-danger bg-opacity-10
                        @elseif($meta['risk'] === 'medium') border border-warning bg-warning bg-opacity-10
                        @else border
                        @endif"
                        style="transition:box-shadow .15s">
                        {{-- Icon + label --}}
                        <div class="flex-shrink-0 mt-1">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-2
                                         bg-{{ $meta['color'] }} bg-opacity-10 text-{{ $meta['color'] }}"
                                  style="width:34px;height:34px;font-size:1rem">
                                <i class="bi {{ $meta['icon'] }}"></i>
                            </span>
                        </div>
                        <div style="min-width:0">
                            <div class="d-flex align-items-center gap-1 flex-wrap mb-1">
                                <span class="fw-700" style="font-size:.82rem">{{ $meta['label'] }}</span>
                                @if($meta['risk'] === 'high')
                                    <span class="badge bg-danger rounded-pill" style="font-size:.6rem">High risk</span>
                                @elseif($meta['risk'] === 'medium')
                                    <span class="badge bg-warning text-dark rounded-pill" style="font-size:.6rem">Medium risk</span>
                                @endif
                            </div>
                            <p class="text-muted mb-1" style="font-size:.775rem;line-height:1.45">
                                {{ $meta['desc'] }}
                            </p>
                            <p class="mb-0" style="font-size:.74rem;color:#64748b;line-height:1.4">
                                {{ $meta['detail'] }}
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach
</div>

{{-- ── Main content: roles + create form ───────────────────── --}}
<div class="row g-4 align-items-start">

    {{-- ── Role cards ─────────────────────────────────────── --}}
    <div class="col-lg-8">
        @forelse($roles as $role)
        @php $usersInRole = \App\Models\User::where('role_id', $role->id)->count(); @endphp
        <div class="page-card mb-4">

            {{-- Role header --}}
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3 pb-3"
                 style="border-bottom:1px solid var(--color-border)">
                <div>
                    <h5 class="fw-700 mb-1 d-flex align-items-center gap-2" style="font-size:1rem">
                        <span class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-2"
                              style="width:30px;height:30px;font-size:.9rem">
                            <i class="bi bi-person-badge"></i>
                        </span>
                        {{ $role->name }}
                    </h5>
                    <div class="text-muted" style="font-size:.8rem">
                        <i class="bi bi-key me-1"></i>{{ $role->permissions->count() }} {{ Str::plural('permission', $role->permissions->count()) }}
                        &ensp;·&ensp;
                        <i class="bi bi-people me-1"></i>{{ $usersInRole }} {{ Str::plural('user', $usersInRole) }}
                    </div>
                </div>
                @if($usersInRole === 0)
                    <form action="{{ route('admin.roles.destroy', $role) }}" method="POST"
                          onsubmit="return confirm('Permanently delete the role \'{{ $role->name }}\'?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i>Delete
                        </button>
                    </form>
                @else
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border"
                          style="font-size:.72rem" title="Cannot delete: users are assigned to this role">
                        <i class="bi bi-lock me-1"></i>{{ $usersInRole }} {{ Str::plural('user', $usersInRole) }} assigned
                    </span>
                @endif
            </div>

            {{-- Permissions form grouped by category --}}
            <form action="{{ route('admin.roles.updatePermissions', $role) }}" method="POST">
                @csrf @method('PATCH')

                @foreach($groups as $groupName => $groupMeta)
                    @php
                        $groupPerms = $permissions->filter(
                            fn($p) => ($permissionMeta[$p->name]['group'] ?? '') === $groupName
                        );
                    @endphp
                    @if($groupPerms->isNotEmpty())
                    <div class="mb-4">
                        {{-- Group heading --}}
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi {{ $groupMeta['icon'] }} text-{{ $groupMeta['color'] }}"
                               style="font-size:.85rem"></i>
                            <span class="fw-700 text-uppercase"
                                  style="font-size:.68rem;letter-spacing:.07em;color:var(--color-muted)">
                                {{ $groupName }}
                            </span>
                        </div>

                        <div class="row g-2">
                            @foreach($groupPerms as $permission)
                                @php
                                    $checked = $role->permissions->contains('id', $permission->id);
                                    $meta    = $permissionMeta[$permission->name] ?? [
                                        'label'  => $permission->name,
                                        'icon'   => 'bi-key',
                                        'color'  => 'secondary',
                                        'risk'   => null,
                                        'desc'   => '',
                                        'detail' => '',
                                    ];
                                @endphp
                                <div class="col-12">
                                    <label class="d-flex gap-3 p-3 rounded-3 border h-100 position-relative"
                                           style="cursor:pointer;transition:border-color .15s,background .15s;
                                                  {{ $checked ? 'border-color:var(--color-primary)!important;background:var(--color-primary-soft)' : '' }}
                                                  {{ $meta['risk'] === 'high' && !$checked ? 'border-color:#fca5a5' : '' }}">
                                        <input type="checkbox"
                                               class="form-check-input flex-shrink-0 mt-1"
                                               name="permissions[]"
                                               value="{{ $permission->id }}"
                                               {{ $checked ? 'checked' : '' }}>

                                        {{-- Icon --}}
                                        <span class="d-inline-flex align-items-center justify-content-center flex-shrink-0
                                                     bg-{{ $meta['color'] }} bg-opacity-10 text-{{ $meta['color'] }} rounded-2"
                                              style="width:30px;height:30px;font-size:.85rem;margin-top:.05rem">
                                            <i class="bi {{ $meta['icon'] }}"></i>
                                        </span>

                                        {{-- Text --}}
                                        <div style="min-width:0;flex:1">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <span class="fw-700" style="font-size:.84rem">{{ $meta['label'] }}</span>
                                                @if($meta['risk'] === 'high')
                                                    <span class="badge bg-danger rounded-pill" style="font-size:.6rem">
                                                        <i class="bi bi-x-octagon me-1"></i>High risk
                                                    </span>
                                                @elseif($meta['risk'] === 'medium')
                                                    <span class="badge bg-warning text-dark rounded-pill" style="font-size:.6rem">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>Medium risk
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="mb-0 mt-1" style="font-size:.78rem;color:#475569;line-height:1.45">
                                                {{ $meta['detail'] }}
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endforeach

                <div class="d-flex align-items-center gap-3 pt-2" style="border-top:1px solid var(--color-border)">
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="bi bi-check-circle me-1"></i>Save Permissions
                    </button>
                    <span class="text-muted" style="font-size:.78rem">
                        Changes take effect immediately for all users in this role.
                    </span>
                </div>
            </form>
        </div>
        @empty
        <div class="page-card text-center py-5 text-muted">
            <i class="bi bi-shield-slash d-block mb-2" style="font-size:2.5rem;opacity:.3"></i>
            <p class="mb-0">No roles yet. Create one to get started.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Sidebar: Create role + quick list ───────────────── --}}
    <div class="col-lg-4">
        <div class="page-card sticky-top" style="top:76px">

            {{-- Create form --}}
            <h6 class="fw-700 mb-1" style="font-size:.9rem">
                <i class="bi bi-plus-circle text-success me-2"></i>Create New Role
            </h6>
            <p class="text-muted mb-3" style="font-size:.8rem">
                New roles start with no permissions. You'll assign them after creation.
            </p>

            <form action="{{ route('admin.roles.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="role_name" class="form-label">
                        Role Name <span class="text-danger">*</span>
                    </label>
                    <input type="text"
                           class="form-control @error('name') is-invalid @enderror"
                           id="role_name"
                           name="name"
                           placeholder="e.g. Manager, Viewer, Supervisor…"
                           value="{{ old('name') }}"
                           maxlength="50"
                           autocomplete="off">
                    @error('name')
                        <div class="invalid-feedback">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                        </div>
                    @enderror
                    <div class="form-text">Letters and spaces, max 50 characters.</div>
                </div>
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-plus-lg me-1"></i>Create Role
                </button>
            </form>

            <hr class="my-4">

            {{-- Quick summary --}}
            <div class="section-title">Existing Roles</div>
            <div class="d-flex flex-column gap-2">
                @foreach($roles as $role)
                @php $count = \App\Models\User::where('role_id', $role->id)->count(); @endphp
                <div class="d-flex justify-content-between align-items-center p-2 rounded-2"
                     style="background:var(--color-bg)">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-badge text-primary"></i>
                        <span class="fw-600" style="font-size:.84rem">{{ $role->name }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted" style="font-size:.75rem">
                            {{ $role->permissions->count() }} perms
                        </span>
                        <span class="badge bg-white border text-muted" style="font-size:.7rem">
                            {{ $count }} {{ Str::plural('user', $count) }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>

            <hr class="my-4">

            {{-- Tips --}}
            <div class="rounded-3 p-3" style="background:#fefce8;border:1px solid #fde68a">
                <p class="fw-700 mb-2" style="font-size:.8rem;color:#92400e">
                    <i class="bi bi-lightbulb me-1"></i>Tips
                </p>
                <ul class="mb-0 ps-3" style="font-size:.775rem;color:#78350f;line-height:1.6">
                    <li>A role with <strong>Manage Users & Roles</strong> can modify any other role — grant it carefully.</li>
                    <li><strong>High risk</strong> permissions (Force Delete, Force Transition) should only go to trusted Admins.</li>
                    <li>Clients typically only need <strong>Create Request</strong> and <strong>View Requests</strong>.</li>
                    <li>Changes apply instantly — no page reload needed for affected users.</li>
                </ul>
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
/* Highlight checkbox card on toggle */
document.querySelectorAll('label.border input[type="checkbox"]').forEach(cb => {
    cb.addEventListener('change', () => {
        const label = cb.closest('label');
        if (cb.checked) {
            label.style.borderColor = 'var(--color-primary)';
            label.style.background  = 'var(--color-primary-soft)';
        } else {
            label.style.borderColor = '';
            label.style.background  = '';
        }
    });
});
</script>
@endpush
