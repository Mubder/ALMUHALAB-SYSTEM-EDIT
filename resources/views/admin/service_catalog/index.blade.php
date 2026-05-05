@extends('layouts.app')
@section('title', 'Service Catalog')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0"><i class="bi bi-grid-3x3-gap text-primary me-2"></i>Service Catalog</h1>
        <p class="text-muted small mb-0">Define services and map them to journey stages</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Admin Panel
    </a>
</div>

<div class="row g-4 align-items-start">

    {{-- ── Left: Services Table ──────────────────────────── --}}
    <div class="col-lg-7">
        <div class="bg-white rounded-3 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr class="bg-light border-bottom">
                            <th class="ps-4 text-muted small fw-600" style="width:5%">#</th>
                            <th class="text-muted small fw-600" style="width:22%">NAME</th>
                            <th class="text-muted small fw-600" style="width:28%">DESCRIPTION</th>
                            <th class="text-muted small fw-600" style="width:10%">STATUS</th>
                            <th class="text-muted small fw-600" style="width:10%">USES</th>
                            <th class="text-muted small fw-600 pe-4">STAGES MAPPED</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $svc)
                        <tr class="border-bottom" id="svc-row-{{ $svc->id }}">
                            <td class="ps-4 text-muted small">{{ $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-{{ $svc->color }}" style="font-size:1.1rem">
                                        <i class="bi {{ $svc->icon }}"></i>
                                    </span>
                                    <span class="fw-500">{{ $svc->name }}</span>
                                </div>
                            </td>
                            <td class="text-muted small">{{ Str::limit($svc->description, 55) ?: '—' }}</td>
                            <td>
                                <span class="badge {{ $svc->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $svc->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $svc->request_services_count }}</span>
                            </td>
                            <td class="pe-4">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    @php $mapped = $svc->mappedStages(); @endphp
                                    @if(count($mapped))
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach(array_slice($mapped, 0, 3) as $stage)
                                                <span class="badge bg-light text-dark border" style="font-size:.65rem">
                                                    {{ \App\Models\FollowUp::STATUS_TYPES[$stage]['label'] ?? $stage }}
                                                </span>
                                            @endforeach
                                            @if(count($mapped) > 3)
                                                <span class="text-muted" style="font-size:.72rem">+{{ count($mapped) - 3 }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                    <div class="d-flex gap-1 ms-auto">
                                        <button class="btn btn-outline-primary btn-sm btn-action"
                                                onclick="openEdit({{ $svc->id }}, '{{ addslashes($svc->name) }}', '{{ addslashes($svc->description ?? '') }}', '{{ $svc->icon }}', '{{ $svc->color }}', {{ $svc->is_active ? 'true' : 'false' }}, {{ json_encode($mapped) }})">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        @if($svc->request_services_count === 0)
                                        <form action="{{ route('admin.service-catalog.destroy', $svc) }}" method="POST"
                                              onsubmit="return confirm('Delete \'{{ $svc->name }}\'?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm btn-action">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        @else
                                        <span class="text-muted" title="Has active uses"><i class="bi bi-lock"></i></span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-grid-3x3-gap d-block mb-2" style="font-size:1.5rem;opacity:.3"></i>
                                No services defined yet. Add your first one →
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Right: Create / Edit Forms ───────────────────── --}}
    <div class="col-lg-5">

        {{-- Create Form --}}
        <div class="bg-white rounded-3 shadow-sm p-4 mb-3" id="create-form">
            <h6 class="fw-bold mb-3"><i class="bi bi-plus-circle text-success me-1"></i>Add Service</h6>
            <form action="{{ route('admin.service-catalog.store') }}" method="POST">
                @csrf
                <div class="mb-2">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control form-control-sm @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" placeholder="e.g. Hotel Booking">
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-2">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" class="form-control form-control-sm"
                           value="{{ old('description') }}" placeholder="Short description">
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-7">
                        <label class="form-label">Icon <span class="text-muted fw-normal">(Bootstrap Icons)</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" id="create-icon-preview"><i class="bi bi-star"></i></span>
                            <input type="text" name="icon" id="create-icon-input"
                                   class="form-control form-control-sm"
                                   value="{{ old('icon', 'bi-star') }}" placeholder="bi-building">
                        </div>
                    </div>
                    <div class="col-5">
                        <label class="form-label">Color</label>
                        <select name="color" id="create-color" class="form-select form-select-sm">
                            @foreach(\App\Models\ServiceCatalog::COLORS as $c)
                                <option value="{{ $c }}" {{ old('color','primary') === $c ? 'selected':'' }}>{{ ucfirst($c) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Map to Stages</label>
                    <div class="border rounded-3 p-2" style="max-height:180px;overflow-y:auto">
                        @foreach($stageTypes as $key => $cfg)
                        <div class="form-check form-check-sm">
                            <input class="form-check-input" type="checkbox" name="stages[]"
                                   value="{{ $key }}" id="cs_{{ $key }}">
                            <label class="form-check-label small" for="cs_{{ $key }}">
                                <i class="bi {{ $cfg['icon'] }} text-{{ $cfg['color'] }} me-1"></i>{{ $cfg['label'] }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="btn btn-success btn-sm w-100">
                    <i class="bi bi-plus-lg me-1"></i>Create Service
                </button>
            </form>
        </div>

        {{-- Edit Form (hidden by default) --}}
        <div class="bg-white rounded-3 shadow-sm p-4 border border-primary d-none" id="edit-form">
            <h6 class="fw-bold mb-3"><i class="bi bi-pencil text-primary me-1"></i>Edit Service</h6>
            <form id="edit-form-inner" method="POST">
                @csrf @method('PUT')
                <div class="mb-2">
                    <label class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="edit-name" class="form-control form-control-sm" required>
                </div>
                <div class="mb-2">
                    <label class="form-label">Description</label>
                    <input type="text" name="description" id="edit-description" class="form-control form-control-sm">
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-7">
                        <label class="form-label">Icon</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" id="edit-icon-preview"><i class="bi bi-star"></i></span>
                            <input type="text" name="icon" id="edit-icon-input"
                                   class="form-control form-control-sm" placeholder="bi-building">
                        </div>
                    </div>
                    <div class="col-5">
                        <label class="form-label">Color</label>
                        <select name="color" id="edit-color" class="form-select form-select-sm">
                            @foreach(\App\Models\ServiceCatalog::COLORS as $c)
                                <option value="{{ $c }}">{{ ucfirst($c) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="edit-is-active" class="form-check-input" value="1">
                        <label class="form-check-label small" for="edit-is-active">Active</label>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Map to Stages</label>
                    <div class="border rounded-3 p-2" style="max-height:180px;overflow-y:auto" id="edit-stages-container">
                        @foreach($stageTypes as $key => $cfg)
                        <div class="form-check form-check-sm">
                            <input class="form-check-input edit-stage-check" type="checkbox"
                                   name="stages[]" value="{{ $key }}" id="es_{{ $key }}">
                            <label class="form-check-label small" for="es_{{ $key }}">
                                <i class="bi {{ $cfg['icon'] }} text-{{ $cfg['color'] }} me-1"></i>{{ $cfg['label'] }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                        <i class="bi bi-check-circle me-1"></i>Save
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="closeEdit()">Cancel</button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
// Icon live preview
function wireIconPreview(inputId, previewId) {
    const input   = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    input.addEventListener('input', () => {
        preview.innerHTML = '<i class="bi ' + input.value + '"></i>';
    });
}
wireIconPreview('create-icon-input', 'create-icon-preview');
wireIconPreview('edit-icon-input',   'edit-icon-preview');

function openEdit(id, name, description, icon, color, isActive, mappedStages) {
    document.getElementById('edit-form-inner').action = '/admin/service-catalog/' + id;
    document.getElementById('edit-name').value        = name;
    document.getElementById('edit-description').value = description;
    document.getElementById('edit-icon-input').value  = icon;
    document.getElementById('edit-icon-preview').innerHTML = '<i class="bi ' + icon + '"></i>';
    document.getElementById('edit-is-active').checked = isActive;

    // Set color select
    const colorSel = document.getElementById('edit-color');
    for (let opt of colorSel.options) { opt.selected = opt.value === color; }

    // Set stage checkboxes
    document.querySelectorAll('.edit-stage-check').forEach(cb => {
        cb.checked = mappedStages.includes(cb.value);
    });

    document.getElementById('edit-form').classList.remove('d-none');
    document.getElementById('create-form').classList.add('d-none');
    document.getElementById('edit-form').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function closeEdit() {
    document.getElementById('edit-form').classList.add('d-none');
    document.getElementById('create-form').classList.remove('d-none');
}
</script>

@endsection
