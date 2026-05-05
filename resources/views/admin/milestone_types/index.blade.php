@extends('layouts.app')
@section('title', 'Milestone Types')

@push('styles')
<style>
.icon-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(2.4rem, 1fr));
    gap: .35rem;
    max-height: 260px;
    overflow-y: auto;
    padding: .5rem;
    border: 1px solid var(--bs-border-color);
    border-radius: .5rem;
    background: #fff;
}
.icon-grid button {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2.4rem;
    height: 2.4rem;
    border: 1px solid transparent;
    border-radius: .4rem;
    background: none;
    font-size: 1.1rem;
    cursor: pointer;
    color: #555;
    transition: background .12s, border-color .12s, color .12s;
}
.icon-grid button:hover  { background: var(--bs-primary-bg-subtle); border-color: var(--bs-primary); color: var(--bs-primary); }
.icon-grid button.active { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
.icon-search { font-size: .85rem; }
.icon-preview-badge { font-size: 1.6rem; }
</style>
@endpush

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0"><i class="bi bi-diagram-3 text-primary me-2"></i>Milestone Types</h1>
        <p class="text-muted small mb-0">Control the milestones available in the Journey Timeline</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Admin Panel
    </a>
</div>

<div class="row g-4 align-items-start">

    {{-- ── LEFT: List ───────────────────────────────────── --}}
    <div class="col-lg-8">
        <div class="bg-white rounded-3 shadow-sm overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr class="bg-light border-bottom">
                            <th class="ps-4 text-muted small" style="width:3rem">#</th>
                            <th class="text-muted small">Icon</th>
                            <th class="text-muted small">Label</th>
                            <th class="text-muted small">Key</th>
                            <th class="text-muted small">Color</th>
                            <th class="text-muted small">Status</th>
                            <th class="text-muted small pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($milestones as $m)
                        <tr class="border-bottom {{ $m->is_active ? '' : 'opacity-50' }}">
                            <td class="ps-4 text-muted small">{{ $m->sort_order }}</td>
                            <td>
                                <span class="text-{{ $m->color }}" style="font-size:1.4rem">
                                    <i class="bi {{ $m->icon }}"></i>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $m->color }}-subtle text-{{ $m->color }} border border-{{ $m->color }}-subtle">
                                    <i class="bi {{ $m->icon }} me-1"></i>{{ $m->label }}
                                </span>
                            </td>
                            <td><code class="small">{{ $m->key }}</code></td>
                            <td>
                                <span class="badge bg-{{ $m->color }}">{{ \App\Models\MilestoneType::COLORS[$m->color] ?? $m->color }}</span>
                            </td>
                            <td>
                                @if($m->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:.68rem">Active</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle" style="font-size:.68rem">Disabled</span>
                                @endif
                            </td>
                            <td class="pe-4">
                                <div class="d-flex gap-1">
                                    <button class="btn btn-outline-primary btn-sm btn-action"
                                            onclick="openEdit({{ $m->id }}, '{{ $m->label }}', '{{ $m->icon }}', '{{ $m->color }}', {{ $m->sort_order }}, {{ $m->is_active ? 'true' : 'false' }})"
                                            title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form action="{{ route('admin.milestone-types.destroy', $m) }}" method="POST"
                                          onsubmit="return confirm('Delete \'{{ $m->label }}\'? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm btn-action" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-diagram-3 d-block mb-2" style="font-size:1.5rem;opacity:.3"></i>
                                No milestones yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Add Form ───────────────────────────────── --}}
    <div class="col-lg-4">
        <div class="page-card" id="formCard">
            <h6 class="fw-bold mb-3" id="formTitle">
                <i class="bi bi-plus-circle text-primary me-1"></i>Add Milestone
            </h6>

            <form id="milestoneForm" action="{{ route('admin.milestone-types.store') }}" method="POST">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="_id" id="formId">

                {{-- Icon picker --}}
                <div class="mb-3">
                    <label class="form-label">Icon <span class="text-danger">*</span></label>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="icon-preview-badge" id="iconPreview"><i class="bi bi-circle text-secondary"></i></span>
                        <input type="text" name="icon" id="iconInput" class="form-control form-control-sm"
                               placeholder="bi-circle" required oninput="updateIconPreview(this.value)">
                    </div>
                    <input type="text" id="iconSearch" class="form-control form-control-sm icon-search mb-2"
                           placeholder="Search icons… e.g. check, calendar, send">
                    <div class="icon-grid" id="iconGrid"></div>
                    <div class="form-text">Click an icon or type its name manually.</div>
                </div>

                {{-- Label --}}
                <div class="mb-3">
                    <label class="form-label">Label <span class="text-danger">*</span></label>
                    <input type="text" name="label" id="labelInput" class="form-control form-control-sm"
                           placeholder="e.g. Visa Submitted" required>
                </div>

                {{-- Key (add only) --}}
                <div class="mb-3" id="keyField">
                    <label class="form-label">Key <span class="text-danger">*</span></label>
                    <input type="text" name="key" id="keyInput" class="form-control form-control-sm font-monospace"
                           placeholder="e.g. visa_submitted" pattern="[a-z0-9_]+" required>
                    <div class="form-text">Lowercase letters, numbers, underscores only. Cannot be changed later.</div>
                </div>

                {{-- Color --}}
                <div class="mb-3">
                    <label class="form-label">Color <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-2" id="colorPicker">
                        @foreach(\App\Models\MilestoneType::COLORS as $val => $name)
                        <label class="d-flex align-items-center gap-1 cursor-pointer">
                            <input type="radio" name="color" value="{{ $val }}" class="d-none color-radio"
                                   {{ $val === 'secondary' ? 'checked' : '' }}
                                   onchange="updateColorPreview()">
                            <span class="badge bg-{{ $val }} px-2 py-1" style="font-size:.75rem;cursor:pointer"
                                  onclick="this.previousElementSibling.click()">
                                {{ $name }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Sort Order --}}
                <div class="mb-3">
                    <label class="form-label">Order</label>
                    <input type="number" name="sort_order" id="sortInput" class="form-control form-control-sm"
                           min="0" value="{{ $milestones->count() + 1 }}">
                </div>

                {{-- Active toggle (edit only) --}}
                <div class="mb-3 d-none" id="activeField">
                    <div class="form-check form-switch">
                        <input type="checkbox" name="is_active" id="activeInput" class="form-check-input" value="1" checked>
                        <label class="form-check-label small" for="activeInput">Active (visible in dropdowns)</label>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1" id="submitBtn">
                        <i class="bi bi-plus-lg me-1"></i>Add
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="cancelBtn" onclick="resetForm()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ── Icon library (Bootstrap Icons subset) ─────────────────────
const ICONS = [
    'bi-inbox-fill','bi-eye-fill','bi-eye','bi-file-earmark-text','bi-gear-fill','bi-gear',
    'bi-calendar-check-fill','bi-calendar-check','bi-calendar-event','bi-calendar',
    'bi-ticket-perforated-fill','bi-ticket-perforated','bi-send-fill','bi-send',
    'bi-patch-check-fill','bi-patch-check','bi-check-circle-fill','bi-check-circle',
    'bi-info-circle-fill','bi-info-circle','bi-sticky-fill','bi-sticky',
    'bi-clock-fill','bi-clock','bi-hourglass-split','bi-hourglass',
    'bi-person-check-fill','bi-person-check','bi-person-fill','bi-person',
    'bi-building','bi-house-fill','bi-house','bi-map-fill','bi-map',
    'bi-airplane-fill','bi-airplane','bi-train-fill','bi-train',
    'bi-briefcase-fill','bi-briefcase','bi-bag-fill','bi-bag',
    'bi-currency-dollar','bi-credit-card-fill','bi-credit-card',
    'bi-telephone-fill','bi-telephone','bi-envelope-fill','bi-envelope',
    'bi-chat-fill','bi-chat','bi-bell-fill','bi-bell',
    'bi-shield-check','bi-shield-fill','bi-lock-fill','bi-lock',
    'bi-star-fill','bi-star','bi-bookmark-fill','bi-bookmark',
    'bi-flag-fill','bi-flag','bi-exclamation-triangle-fill','bi-exclamation-circle-fill',
    'bi-x-circle-fill','bi-x-circle','bi-dash-circle-fill','bi-plus-circle-fill',
    'bi-arrow-right-circle-fill','bi-arrow-left-circle-fill','bi-arrow-repeat',
    'bi-file-earmark-pdf-fill','bi-file-earmark-pdf','bi-file-earmark-check-fill',
    'bi-clipboard-check-fill','bi-clipboard-check','bi-list-check','bi-card-checklist',
    'bi-receipt','bi-journal-text','bi-newspaper','bi-printer-fill',
    'bi-globe','bi-globe2','bi-wifi','bi-cloud-check-fill',
    'bi-lightbulb-fill','bi-lightbulb','bi-tools','bi-wrench-adjustable',
    'bi-circle-fill','bi-circle','bi-square-fill','bi-hexagon-fill',
];

let currentIcon = 'bi-circle';

function renderIconGrid(filter = '') {
    const grid   = document.getElementById('iconGrid');
    const active = document.getElementById('iconInput').value.trim();
    const list   = filter
        ? ICONS.filter(i => i.includes(filter.toLowerCase()))
        : ICONS;
    grid.innerHTML = list.map(icon =>
        `<button type="button" title="${icon}" class="${icon === active ? 'active' : ''}"
                 onclick="selectIcon('${icon}')">
            <i class="bi ${icon}"></i>
         </button>`
    ).join('');
}

function selectIcon(icon) {
    document.getElementById('iconInput').value = icon;
    updateIconPreview(icon);
    renderIconGrid(document.getElementById('iconSearch').value);
}

function updateIconPreview(val) {
    const v = val.trim() || 'bi-circle';
    document.getElementById('iconPreview').innerHTML = `<i class="bi ${v}"></i>`;
    renderIconGrid(document.getElementById('iconSearch').value);
}

document.getElementById('iconSearch').addEventListener('input', function() {
    renderIconGrid(this.value);
});

// Auto-generate key from label (add mode only)
document.getElementById('labelInput').addEventListener('input', function() {
    if (document.getElementById('formMethod').value === 'POST') {
        const key = this.value.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
        document.getElementById('keyInput').value = key;
    }
});

function updateColorPreview() {}

function openEdit(id, label, icon, color, sortOrder, isActive) {
    document.getElementById('formTitle').innerHTML = '<i class="bi bi-pencil text-warning me-1"></i>Edit Milestone';
    document.getElementById('formMethod').value    = 'PUT';
    document.getElementById('formId').value        = id;
    document.getElementById('milestoneForm').action = `/admin/milestone-types/${id}`;
    document.getElementById('labelInput').value    = label;
    document.getElementById('iconInput').value     = icon;
    document.getElementById('sortInput').value     = sortOrder;
    document.getElementById('keyField').classList.add('d-none');
    document.getElementById('activeField').classList.remove('d-none');
    document.getElementById('activeInput').checked = isActive;
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-check-circle me-1"></i>Save';
    document.getElementById('cancelBtn').classList.remove('d-none');

    // Set color radio
    document.querySelectorAll('.color-radio').forEach(r => r.checked = r.value === color);

    updateIconPreview(icon);
    document.getElementById('formCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetForm() {
    document.getElementById('formTitle').innerHTML = '<i class="bi bi-plus-circle text-primary me-1"></i>Add Milestone';
    document.getElementById('formMethod').value    = 'POST';
    document.getElementById('formId').value        = '';
    document.getElementById('milestoneForm').action = '{{ route("admin.milestone-types.store") }}';
    document.getElementById('milestoneForm').reset();
    document.getElementById('keyField').classList.remove('d-none');
    document.getElementById('activeField').classList.add('d-none');
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-plus-lg me-1"></i>Add';
    document.getElementById('cancelBtn').classList.add('d-none');
    updateIconPreview('bi-circle');
}

// Init
renderIconGrid();
</script>
@endpush
