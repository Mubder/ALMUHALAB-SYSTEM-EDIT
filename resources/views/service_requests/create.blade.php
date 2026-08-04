@extends('layouts.app')
@section('title', __('New Request'))

@php
$phoneCodes = [
    '+966' => '🇸🇦 +966 Saudi Arabia',
    '+971' => '🇦🇪 +971 UAE',
    '+965' => '🇰🇼 +965 Kuwait',
    '+973' => '🇧🇭 +973 Bahrain',
    '+974' => '🇶🇦 +974 Qatar',
    '+968' => '🇴🇲 +968 Oman',
    '+962' => '🇯🇴 +962 Jordan',
    '+20'  => '🇪🇬 +20 Egypt',
    '+90'  => '🇹🇷 +90 Turkey',
    '+7'   => '🇷🇺 +7 Russia',
    '+49'  => '🇩🇪 +49 Germany',
    '+44'  => '🇬🇧 +44 United Kingdom',
    '+1'   => '🇺🇸 +1 USA / Canada',
    '+33'  => '🇫🇷 +33 France',
    '+39'  => '🇮🇹 +39 Italy',
    '+34'  => '🇪🇸 +34 Spain',
    '+91'  => '🇮🇳 +91 India',
    '+92'  => '🇵🇰 +92 Pakistan',
    '+63'  => '🇵🇭 +63 Philippines',
];
$isStaff = auth()->user()->hasPermission('edit_request');
@endphp

@section('content')
<div class="row justify-content-center">
<div class="col-lg-9">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('service-requests.index') }}">{{ __('Requests') }}</a></li>
            <li class="breadcrumb-item active">{{ __('New Request') }}</li>
        </ol>
    </nav>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>{{ __('Please fix the errors below before submitting.') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('service-requests.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    {{-- ── Section 1: Client Information ──────────────────────── --}}
    <div class="page-card mb-4">
        <h6 class="fw-bold mb-4 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
            <i class="bi bi-person-fill me-1"></i>{{ __('Client Information') }}
        </h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('Full Name') }} <span class="text-danger">*</span></label>
                <input type="text" name="client_name"
                       class="form-control @error('client_name') is-invalid @enderror"
                       value="{{ old('client_name') }}"
                       placeholder="{{ __('e.g. Ahmed Al-Mansouri') }}">
                @error('client_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('Email') }}</label>
                <input type="email" name="client_email"
                       class="form-control @error('client_email') is-invalid @enderror"
                       value="{{ old('client_email') }}"
                       placeholder="example@email.com">
                @error('client_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('Phone Number') }}</label>
                <div class="input-group">
                    <select name="client_phone_code"
                            class="form-select @error('client_phone_code') is-invalid @enderror"
                            style="max-width:200px">
                        <option value="">{{ __('Code') }}</option>
                        @foreach($phoneCodes as $code => $label)
                            <option value="{{ $code }}" {{ old('client_phone_code', '+966') === $code ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="client_phone"
                           class="form-control @error('client_phone') is-invalid @enderror"
                           value="{{ old('client_phone') }}"
                           placeholder="5XXXXXXXX">
                    @error('client_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('Country') }}</label>
                <input type="text" name="client_country"
                       class="form-control @error('client_country') is-invalid @enderror"
                       value="{{ old('client_country') }}"
                       placeholder="{{ __('e.g. Saudi Arabia') }}">
                @error('client_country')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        @if($isStaff)
        <div class="mt-4 pt-3 border-top d-flex align-items-center gap-2 flex-wrap" style="font-size:.8rem">
            <i class="bi bi-eye text-warning"></i>
            <span class="text-muted">{{ __('Section visible to') }}:</span>
            <select name="fv[client_info]" class="form-select form-select-sm" style="max-width:180px">
                <option value="all">{{ __('Everyone') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    {{-- ── Section 2: Request Details ──────────────────────────── --}}
    <div class="page-card mb-4">
        <h6 class="fw-bold mb-4 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
            <i class="bi bi-file-text me-1"></i>{{ __('Request Details') }}
        </h6>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
                <input type="text" name="title"
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}"
                       placeholder="{{ __('Brief title for this request') }}">
                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('Service Type') }}</label>
                <select name="service_type_id" class="form-select @error('service_type_id') is-invalid @enderror">
                    <option value="">— {{ __('Select Type') }} —</option>
                    @foreach($serviceTypes as $type)
                        <option value="{{ $type->id }}" {{ old('service_type_id') == $type->id ? 'selected' : '' }}>
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>
                @error('service_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('Description') }} <span class="text-danger">*</span></label>
                <textarea name="description" rows="4"
                          class="form-control @error('description') is-invalid @enderror"
                          placeholder="{{ __('Describe your request in detail…') }}">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

@if(auth()->user()->hasPermission('update_status'))
            <div class="col-md-4">
                <label class="form-label">{{ __('Status') }}</label>
                <select name="status" class="form-select">
                    @foreach(['New','Under Review','Approved','Rejected','Completed'] as $s)
                        <option value="{{ $s }}" {{ old('status','New') === $s ? 'selected':'' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            @if($isStaff)
            <div class="col-md-4">
                <label class="form-label">{{ __('Assign To') }}</label>
                <select name="assigned_to" class="form-select">
                    <option value="">— {{ __('Unassigned') }} —</option>
                    @foreach($staffUsers as $su)
                        <option value="{{ $su->id }}" {{ old('assigned_to') == $su->id ? 'selected':'' }}>
                            {{ $su->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
        @if($isStaff)
        <div class="mt-4 pt-3 border-top d-flex align-items-center gap-2 flex-wrap" style="font-size:.8rem">
            <i class="bi bi-eye text-warning"></i>
            <span class="text-muted">{{ __('Section visible to') }}:</span>
            <select name="fv[description]" class="form-select form-select-sm" style="max-width:180px">
                <option value="all">{{ __('Everyone') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    {{-- ── Section 3: Travel Information ───────────────────────── --}}
    <div class="page-card mb-4">
        <h6 class="fw-bold mb-4 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
            <i class="bi bi-airplane me-1"></i>{{ __('Travel Information') }}
        </h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('Destination Country') }}</label>
                <input type="text" name="destination_country"
                       class="form-control @error('destination_country') is-invalid @enderror"
                       value="{{ old('destination_country') }}"
                       placeholder="{{ __('e.g. Russia') }}">
                @error('destination_country')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('Destination City') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                <input type="text" name="destination_city"
                       class="form-control @error('destination_city') is-invalid @enderror"
                       value="{{ old('destination_city') }}"
                       placeholder="{{ __('e.g. Moscow') }}">
                @error('destination_city')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('Travel Start Date') }}</label>
                <input type="date" name="travel_date_start" id="travel_date_start"
                       class="form-control @error('travel_date_start') is-invalid @enderror"
                       value="{{ old('travel_date_start') }}">
                @error('travel_date_start')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">{{ __('Travel End Date') }}</label>
                <input type="date" name="travel_date_end" id="travel_date_end"
                       class="form-control @error('travel_date_end') is-invalid @enderror"
                       value="{{ old('travel_date_end') }}">
                @error('travel_date_end')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <div class="w-100 bg-light rounded-3 p-2 text-center border">
                    <div class="text-muted small">{{ __('Duration') }}</div>
                    <div class="fw-bold" id="duration-val">—</div>
                </div>
            </div>
        </div>
        @if($isStaff)
        <div class="mt-4 pt-3 border-top d-flex align-items-center gap-2 flex-wrap" style="font-size:.8rem">
            <i class="bi bi-eye text-warning"></i>
            <span class="text-muted">{{ __('Section visible to') }}:</span>
            <select name="fv[travel_info]" class="form-select form-select-sm" style="max-width:180px">
                <option value="all">{{ __('Everyone') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    {{-- ── Section 4: Companions ────────────────────────────────── --}}
    <div class="page-card mb-4">
        <h6 class="fw-bold mb-4 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
            <i class="bi bi-people-fill me-1"></i>{{ __('Companions') }}
        </h6>
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">{{ __('Number of Companions') }}</label>
                <input type="number" name="companions_count" id="companions_count"
                       min="0" max="20"
                       class="form-control @error('companions_count') is-invalid @enderror"
                       value="{{ old('companions_count', 0) }}">
                @error('companions_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div id="companions-container" class="mt-3" style="{{ old('companions_count', 0) > 0 ? '' : 'display:none' }}">
            <div class="text-muted small mb-2">
                <i class="bi bi-info-circle me-1"></i>{{ __('Enter basic information for each companion.') }}
            </div>
            <div id="companions-list"></div>
        </div>

        @if($isStaff)
        <div class="mt-4 pt-3 border-top d-flex align-items-center gap-2 flex-wrap" style="font-size:.8rem">
            <i class="bi bi-eye text-warning"></i>
            <span class="text-muted">{{ __('Section visible to') }}:</span>
            <select name="fv[companions_count]" class="form-select form-select-sm" style="max-width:180px">
                <option value="all">{{ __('Everyone') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    {{-- ── Section 5: Notes & Attachments ──────────────────────── --}}
    <div class="page-card mb-4">
        <h6 class="fw-bold mb-4 text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.07em">
            <i class="bi bi-paperclip me-1"></i>{{ __('Notes & Attachments') }}
        </h6>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">{{ __('Additional Notes') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                <textarea name="additional_notes" rows="3"
                          class="form-control @error('additional_notes') is-invalid @enderror"
                          placeholder="{{ __('Any special requirements or notes…') }}">{{ old('additional_notes') }}</textarea>
                @error('additional_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12">
                <label class="form-label">{{ __('Attachments') }} <span class="text-muted fw-normal">({{ __('optional') }})</span></label>
                <div id="file-rows">
                    <div class="file-row d-flex align-items-center gap-2 mb-2">
                        <input type="file" name="attachments[]" class="form-control form-control-sm">
                        <button type="button" class="btn btn-outline-danger btn-sm flex-shrink-0"
                                onclick="removeFileRow(this)" style="display:none">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addFileRow()">
                    <i class="bi bi-plus me-1"></i>{{ __('Add Another File') }}
                </button>
                <div class="form-text mt-1"><i class="bi bi-info-circle me-1"></i>{{ __('Max 1 GB per file.') }}</div>
                @error('attachments')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                @error('attachments.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
        @if($isStaff)
        <div class="mt-4 pt-3 border-top d-flex align-items-center gap-2 flex-wrap" style="font-size:.8rem">
            <i class="bi bi-eye text-warning"></i>
            <span class="text-muted">{{ __('Section visible to') }}:</span>
            <select name="fv[additional_notes]" class="form-select form-select-sm" style="max-width:180px">
                <option value="all">{{ __('Everyone') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    {{-- Actions --}}
    <div class="d-flex gap-2 mb-5">
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-circle me-1"></i>{{ __('Submit Request') }}
        </button>
        <a href="{{ route('service-requests.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
    </div>

    </form>
</div>
</div>

<script>
// ── Phone codes data for companions ──────────────────────
const phoneCodes = @json(array_keys($phoneCodes));
const phoneLabels = @json(array_values($phoneCodes));

function buildPhoneCodeSelect(name, selected) {
    let opts = '<option value="">Code</option>';
    phoneCodes.forEach((code, i) => {
        const sel = (code === selected) ? 'selected' : '';
        opts += `<option value="${code}" ${sel}>${phoneLabels[i]}</option>`;
    });
    return `<select name="${name}" class="form-select form-select-sm" style="max-width:175px">${opts}</select>`;
}

// ── Companions dynamic rows ──────────────────────────────
const countInput   = document.getElementById('companions_count');
const container    = document.getElementById('companions-container');
const list         = document.getElementById('companions-list');
const oldData      = @json(old('companions_data', []));

function renderCompanions(count) {
    list.innerHTML = '';
    container.style.display = count > 0 ? '' : 'none';
    for (let i = 0; i < count; i++) {
        const d    = oldData[i] || {};
        const code = d.phone_code || '+966';
        list.innerHTML += `
        <div class="border rounded-3 p-3 mb-2 bg-light">
            <div class="fw-semibold small text-muted mb-2">
                <i class="bi bi-person me-1"></i>{{ __('Companion') }} ${i + 1}
            </div>
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="companions_data[${i}][name]"
                           class="form-control form-control-sm"
                           placeholder="{{ __('Full Name') }}"
                           value="${d.name || ''}">
                </div>
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        ${buildPhoneCodeSelect('companions_data[' + i + '][phone_code]', code)}
                        <input type="text" name="companions_data[${i}][phone]"
                               class="form-control form-control-sm"
                               placeholder="{{ __('Phone') }}"
                               value="${d.phone || ''}">
                    </div>
                </div>
                <div class="col-md-4">
                    <input type="email" name="companions_data[${i}][email]"
                           class="form-control form-control-sm"
                           placeholder="{{ __('Email (optional)') }}"
                           value="${d.email || ''}">
                </div>
            </div>
        </div>`;
    }
}

countInput.addEventListener('input', () => renderCompanions(parseInt(countInput.value) || 0));
renderCompanions(parseInt(countInput.value) || 0);

// ── Travel duration calculator ──────────────────────────
const startInput = document.getElementById('travel_date_start');
const endInput   = document.getElementById('travel_date_end');
const durVal     = document.getElementById('duration-val');

function updateDuration() {
    const s = new Date(startInput.value);
    const e = new Date(endInput.value);
    if (startInput.value && endInput.value && e >= s) {
        durVal.textContent = Math.round((e - s) / 86400000) + ' {{ __("day(s)") }}';
    } else {
        durVal.textContent = '—';
    }
}
startInput.addEventListener('change', updateDuration);
endInput.addEventListener('change', updateDuration);
updateDuration();

// ── Dynamic file upload rows ─────────────────────────────
function addFileRow() {
    const container = document.getElementById('file-rows');
    const row = document.createElement('div');
    row.className = 'file-row d-flex align-items-center gap-2 mb-2';
    row.innerHTML = '<input type="file" name="attachments[]" class="form-control form-control-sm">' +
        '<button type="button" class="btn btn-outline-danger btn-sm flex-shrink-0" onclick="removeFileRow(this)">' +
        '<i class="bi bi-trash"></i></button>';
    container.appendChild(row);
    refreshRemoveBtns();
}

function removeFileRow(btn) {
    btn.closest('.file-row').remove();
    refreshRemoveBtns();
}

function refreshRemoveBtns() {
    const rows = document.querySelectorAll('#file-rows .file-row');
    rows.forEach(row => {
        row.querySelector('button').style.display = rows.length > 1 ? '' : 'none';
    });
}
</script>
@endsection
