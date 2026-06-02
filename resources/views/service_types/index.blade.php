@extends('layouts.app')
@section('title', __('Service Types'))

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0"><i class="bi bi-tags text-primary me-2"></i>{{ __('Service Types') }}</h1>
        <p class="text-muted small mb-0">{{ __('Manage the categories available for service requests') }}</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Admin Panel') }}
    </a>
</div>

<div class="row g-4 align-items-start">

    {{-- Left: existing types --}}
    <div class="col-lg-8">
        <div class="bg-white rounded-3 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr class="bg-light border-bottom">
                            <th class="ps-4 text-muted small fw-600" style="width:5%">#</th>
                            <th class="text-muted small fw-600" style="width:25%">{{ __('NAME') }}</th>
                            <th class="text-muted small fw-600" style="width:35%">{{ __('DESCRIPTION') }}</th>
                            <th class="text-muted small fw-600" style="width:10%">{{ __('STATUS') }}</th>
                            <th class="text-muted small fw-600" style="width:10%">{{ __('REQUESTS') }}</th>
                            <th class="text-muted small fw-600 pe-4" style="width:15%">{{ __('ACTIONS') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($types as $type)
                        <tr class="align-middle border-bottom" id="row-{{ $type->id }}">
                            <td class="ps-4 text-muted small">{{ $loop->iteration }}</td>
                            <td class="fw-500">{{ $type->name }}</td>
                            <td class="text-muted small">{{ $type->description ?: '—' }}</td>
                            <td>
                                @if($type->is_active)
                                    <span class="badge bg-success">{{ __('Active') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $type->service_requests_count }}</span>
                            </td>
                            <td class="pe-4">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-outline-primary btn-sm btn-action"
                                            onclick="openEdit({{ $type->id }}, '{{ addslashes($type->name) }}', '{{ addslashes($type->description) }}', {{ $type->is_active ? 'true' : 'false' }})">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @if($type->service_requests_count === 0)
                                    <form action="{{ route('admin.service-types.destroy', $type) }}" method="POST"
                                          onsubmit="return confirm('{{ __('Delete') }} \'{{ $type->name }}\'?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm btn-action">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @else
                                    <span class="text-muted small" title="{{ __('Has linked requests') }}"><i class="bi bi-lock"></i></span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No service types yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Right: create + edit forms --}}
    <div class="col-lg-4">

        {{-- Create --}}
        <div class="bg-white rounded-3 shadow-sm p-4 mb-3" id="create-form">
            <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle text-success me-1"></i>{{ __('Add Service Type') }}</h6>
            <form action="{{ route('admin.service-types.store') }}" method="POST">
                @csrf
                <div class="mb-2">
                    <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control form-control-sm @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" placeholder="{{ __('e.g. Tourism') }}">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('Description') }}</label>
                    <input type="text" name="description" class="form-control form-control-sm"
                           value="{{ old('description') }}" placeholder="{{ __('Short description (optional)') }}">
                </div>
                <button type="submit" class="btn btn-success btn-sm w-100">
                    <i class="bi bi-plus-lg me-1"></i>{{ __('Create Type') }}
                </button>
            </form>
        </div>

        {{-- Edit (hidden by default) --}}
        <div class="bg-white rounded-3 shadow-sm p-4 border border-primary d-none" id="edit-form">
            <h6 class="fw-bold mb-3"><i class="bi bi-pencil text-primary me-1"></i>{{ __('Edit Service Type') }}</h6>
            <form id="edit-form-inner" method="POST">
                @csrf @method('PATCH')
                <div class="mb-2">
                    <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="edit-name" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">{{ __('Description') }}</label>
                    <input type="text" name="description" id="edit-description" class="form-control form-control-sm">
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="edit-is-active" class="form-check-input" value="1">
                        <label class="form-check-label small" for="edit-is-active">{{ __('Active') }}</label>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-check-circle me-1"></i>{{ __('Save') }}
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="closeEdit()">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
function openEdit(id, name, description, isActive) {
    document.getElementById('edit-form-inner').action = '/admin/service-types/' + id;
    document.getElementById('edit-name').value = name;
    document.getElementById('edit-description').value = description;
    document.getElementById('edit-is-active').checked = isActive;
    document.getElementById('edit-form').classList.remove('d-none');
    document.getElementById('create-form').classList.add('d-none');
    document.getElementById('edit-form').scrollIntoView({behavior: 'smooth', block: 'nearest'});
}
function closeEdit() {
    document.getElementById('edit-form').classList.add('d-none');
    document.getElementById('create-form').classList.remove('d-none');
}
</script>

@endsection
