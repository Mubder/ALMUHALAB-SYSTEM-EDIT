@extends('layouts.app')

@section('title', __('User Management'))

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0"><i class="bi bi-people text-primary me-2"></i>{{ __('User Management') }}</h1>
        <p class="text-muted small mb-0">{{ __('Manage users and their roles') }}</p>
    </div>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-shield-lock me-1"></i>{{ __('Manage Roles & Permissions') }}
    </a>
</div>

{{-- Stats --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="page-card text-center py-3">
            <div class="fs-2 fw-bold text-dark">{{ $users->total() }}</div>
            <div class="small text-muted mt-1">{{ __('Total Users') }}</div>
        </div>
    </div>
    @foreach($roles as $role)
    <div class="col-6 col-md-3">
        <div class="page-card text-center py-3">
            <div class="fs-2 fw-bold text-primary">{{ \App\Models\User::where('role_id', $role->id)->count() }}</div>
            <div class="small text-muted mt-1">{{ $role->name }}</div>
        </div>
    </div>
    @endforeach
    <div class="col-6 col-md-3">
        <div class="page-card text-center py-3">
            <div class="fs-2 fw-bold text-warning">{{ \App\Models\User::whereNull('role_id')->count() }}</div>
            <div class="small text-muted mt-1">{{ __('No Role') }}</div>
        </div>
    </div>
</div>

<div class="bg-white rounded-3 shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
                <tr class="bg-light border-bottom">
                    <th class="ps-4 text-muted fw-600 small" style="width:4%">#</th>
                    <th class="text-muted fw-600 small" style="width:22%">{{ __('NAME') }}</th>
                    <th class="text-muted fw-600 small" style="width:22%">{{ __('EMAIL') }}</th>
                    <th class="text-muted fw-600 small" style="width:11%">{{ __('REQUESTS') }}</th>
                    <th class="text-muted fw-600 small" style="width:10%">{{ __('JOINED') }}</th>
                    <th class="text-muted fw-600 small" style="width:16%">{{ __('ROLE') }}</th>
                    <th class="text-muted fw-600 small pe-4" style="width:15%">{{ __('ACTIONS') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr class="border-bottom">
                    <td class="ps-4 text-muted small">
                        {{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle text-white fw-bold flex-shrink-0"
                                  style="width:34px;height:34px;font-size:.8rem;
                                         background:{{ $user->id === auth()->id() ? '#0d6efd' : '#6c757d' }}">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </span>
                            <div>
                                <div class="fw-500 small">
                                    {{ $user->name }}
                                    @if($user->id === auth()->id())
                                        <span class="badge bg-primary ms-1" style="font-size:.62rem">{{ __('You') }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="text-muted small">{{ $user->email }}</td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            {{ $user->service_requests_count }} {{ __('request', ['count' => $user->service_requests_count]) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $user->created_at->format('d M Y') }}</td>
                    <td>
                        <form action="{{ route('admin.users.updateRole', $user) }}" method="POST"
                              class="d-flex align-items-center gap-2">
                            @csrf @method('PATCH')
                            <select name="role_id"
                                    class="form-select form-select-sm"
                                    style="min-width:110px"
                                    onchange="this.form.submit()"
                                    {{ $user->id === auth()->id() ? 'disabled' : '' }}>
                                <option value="">— {{ __('No Role') }} —</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                            @if($user->id === auth()->id())
                                <i class="bi bi-lock text-muted" title="{{ __('Cannot change your own role') }}"></i>
                            @endif
                        </form>
                    </td>
                    <td class="pe-4">
                        <div class="d-flex gap-1">
                            <button class="btn btn-outline-primary btn-sm btn-action"
                                    title="{{ __('Edit user') }}"
                                    onclick="openEditModal({{ $user->id }}, '{{ addslashes($user->name) }}', '{{ addslashes($user->email) }}', '{{ addslashes($user->phone_number ?? '') }}', '{{ addslashes($user->whatsapp_number ?? '') }}', {{ $user->notify_email ? 1 : 0 }}, {{ $user->notify_whatsapp ? 1 : 0 }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            @if($user->id !== auth()->id())
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                  onsubmit="return confirm('{{ __('Delete') }} {{ addslashes($user->name) }}?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm btn-action" title="{{ __('Delete user') }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top">
            <span class="text-muted" style="font-size:.8rem">
                {{ __('Showing :first–:last of :total users', ['first' => $users->firstItem(), 'last' => $users->lastItem(), 'total' => $users->total()]) }}
            </span>
            {{ $users->links('pagination::bootstrap-5') }}
        </div>
    @else
        <div class="px-4 py-2 border-top small text-muted">{{ $users->total() }} {{ __('users') }}</div>
    @endif
</div>

{{-- ── Edit User Modal ────────────────────────────────────── --}}
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>{{ __('Edit User') }}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">{{ __('Full Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="editName" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Email') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="editEmail" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('New Password') }} <span class="text-muted fw-normal">({{ __('leave blank to keep current') }})</span></label>
                            <input type="password" name="password" class="form-control" minlength="8"
                                   placeholder="{{ __('Minimum 8 characters') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Confirm Password') }}</label>
                            <input type="password" name="password_confirmation" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Phone Number') }}</label>
                            <input type="text" name="phone_number" id="editPhone" class="form-control" placeholder="+965...">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-whatsapp text-success me-1"></i>{{ __('WhatsApp Number') }}
                            </label>
                            <input type="text" name="whatsapp_number" id="editWhatsapp" class="form-control" placeholder="+965...">
                        </div>
                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="small fw-600 mb-2 text-muted">{{ __('Notification Preferences') }}</div>
                                <div class="d-flex gap-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="editNotifyEmail" name="notify_email" value="1">
                                        <label class="form-check-label small" for="editNotifyEmail">
                                            <i class="bi bi-envelope me-1"></i>{{ __('Email') }}
                                        </label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="editNotifyWhatsapp" name="notify_whatsapp" value="1">
                                        <label class="form-check-label small" for="editNotifyWhatsapp">
                                            <i class="bi bi-whatsapp text-success me-1"></i>{{ __('WhatsApp') }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>{{ __('Save Changes') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openEditModal(id, name, email, phone, whatsapp, notifyEmail, notifyWhatsapp) {
    document.getElementById('editName').value      = name;
    document.getElementById('editEmail').value     = email;
    document.getElementById('editPhone').value     = phone || '';
    document.getElementById('editWhatsapp').value  = whatsapp || '';
    document.getElementById('editNotifyEmail').checked    = !!notifyEmail;
    document.getElementById('editNotifyWhatsapp').checked = !!notifyWhatsapp;
    document.getElementById('editUserForm').action = `/admin/users/${id}`;
    document.querySelectorAll('#editUserForm input[type=password]').forEach(i => i.value = '');
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>
@endpush
