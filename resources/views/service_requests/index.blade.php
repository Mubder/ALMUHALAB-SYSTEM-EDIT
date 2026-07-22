@extends('layouts.app')
@section('title', __('Service Requests'))

@push('styles')
<style>
.stat-card-clickable {
    transition: transform .15s, box-shadow .15s, border-color .15s;
    cursor: pointer;
    border: 1px solid var(--color-border, #dee2e6) !important;
}
.stat-card-clickable:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,.08);
    border-color: var(--primary, #3e597d) !important;
}
</style>
@endpush

@section('content')

{{-- Stats Bar --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-xl">
        <a href="{{ route('service-requests.index') }}" class="text-decoration-none d-block">
            <div class="stat-card-clickable card text-center py-3 px-2 bg-white h-100">
                <div class="fs-2 fw-bold text-dark">{{ $stats['total'] }}</div>
                <div class="small text-muted mt-1">{{ __('Total') }}</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <a href="{{ route('service-requests.index', ['status' => 'New']) }}" class="text-decoration-none d-block">
            <div class="stat-card-clickable card text-center py-3 px-2 bg-white h-100">
                <div class="fs-2 fw-bold text-primary">{{ $stats['new'] }}</div>
                <div class="small text-muted mt-1">{{ __('New') }}</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <a href="{{ route('service-requests.index', ['status' => 'Under Review']) }}" class="text-decoration-none d-block">
            <div class="stat-card-clickable card text-center py-3 px-2 bg-white h-100">
                <div class="fs-2 fw-bold text-info">{{ $stats['under_review'] }}</div>
                <div class="small text-muted mt-1">{{ __('Under Review') }}</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <a href="{{ route('service-requests.index', ['status' => 'Approved']) }}" class="text-decoration-none d-block">
            <div class="stat-card-clickable card text-center py-3 px-2 bg-white h-100">
                <div class="fs-2 fw-bold text-success">{{ $stats['approved'] }}</div>
                <div class="small text-muted mt-1">{{ __('Approved') }}</div>
            </div>
        </a>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <a href="{{ route('service-requests.index', ['status' => 'Completed']) }}" class="text-decoration-none d-block">
            <div class="stat-card-clickable card text-center py-3 px-2 bg-white h-100">
                <div class="fs-2 fw-bold text-secondary">{{ $stats['completed'] }}</div>
                <div class="small text-muted mt-1">{{ __('Completed') }}</div>
            </div>
        </a>
    </div>
</div>

{{-- Header + New Button --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">{{ __('Service Requests') }}</h1>
        <p class="text-muted small mb-0">
            @if(request()->hasAny(['search','status','service_type_id','date_from','date_to']))
                {{ __('Showing filtered results') }}
            @else
                {{ __('All active requests') }}
            @endif
        </p>
    </div>
    @if(auth()->user()->hasPermission('create_request'))
    <a href="{{ route('service-requests.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>{{ __('New Request') }}
    </a>
    @endif
</div>

{{-- ── Filter Bar ──────────────────────────────────────────── --}}
<div class="page-card mb-4 py-3">
    <form method="GET" action="{{ route('service-requests.index') }}" class="row g-2 align-items-end">

        {{-- Search --}}
        <div class="col-12 col-md-4">
            <label class="form-label small text-muted mb-1">
                <i class="bi bi-search me-1"></i>{{ __('Search') }}
            </label>
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm"
                   placeholder="{{ app()->isLocale('ar') ? 'رقم الطلب، العنوان، الوصف، البلد...' : 'Request no., title, description, country…' }}">
        </div>

        {{-- Status --}}
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1">{{ __('Status') }}</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">{{ __('All Statuses') }}</option>
                @foreach(['New','Under Review','Approved','Rejected','Completed'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>

        {{-- Service Type --}}
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1">{{ __('Service Type') }}</label>
            <select name="service_type_id" class="form-select form-select-sm">
                <option value="">{{ __('All Types') }}</option>
                @foreach($serviceTypes as $type)
                    <option value="{{ $type->id }}" {{ request('service_type_id') == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Date From --}}
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1">{{ __('From') }}</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="form-control form-control-sm">
        </div>

        {{-- Date To --}}
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1">{{ __('To') }}</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="form-control form-control-sm">
        </div>

@if($isAdmin)
        {{-- Assigned To --}}
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1">{{ __('Assigned To') }}</label>
            <select name="assigned_to" class="form-select form-select-sm">
                <option value="">{{ __('Anyone') }}</option>
                <option value="_me" {{ request('assigned_to_me') ? 'selected':'' }}>{{ __('Me') }}</option>
                @foreach($staffUsers as $su)
                    <option value="{{ $su->id }}" {{ request('assigned_to') == $su->id ? 'selected':'' }}>
                        {{ $su->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Buttons --}}
        <div class="col-12 col-md-12 d-flex gap-2 mt-1 flex-wrap">
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-funnel me-1"></i>{{ __('Apply') }}
            </button>
            @if(request()->hasAny(['search','status','service_type_id','date_from','date_to','assigned_to','assigned_to_me']))
                <a href="{{ route('service-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i>{{ __('Clear Filters') }}
                </a>
                <span class="text-muted small align-self-center ms-1">
                    {{ $items->total() }} {{ __('results found') }}
                </span>
            @endif
            <div class="ms-auto d-flex gap-1">
                <a href="{{ route('service-requests.export', array_merge(request()->all(), ['format'=>'csv'])) }}"
                   class="btn btn-outline-success btn-sm" title="{{ __('Export CSV') }}">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>{{ __('CSV') }}
                </a>
                <a href="{{ route('service-requests.export', array_merge(request()->all(), ['format'=>'print'])) }}"
                   target="_blank"
                   class="btn btn-outline-secondary btn-sm" title="{{ __('Print / PDF') }}">
                    <i class="bi bi-printer me-1"></i>{{ __('Print') }}
                </a>
            </div>
        </div>

    </form>
</div>

{{-- Active filter badges --}}
@php
    $activeFilters = array_filter([
        'search'          => request('search'),
        'status'          => request('status'),
        'service_type_id' => request('service_type_id') ? $serviceTypes->find(request('service_type_id'))?->name : null,
        'date_from'       => request('date_from') ? 'From ' . request('date_from') : null,
        'date_to'         => request('date_to')   ? 'To ' . request('date_to')   : null,
    ]);
@endphp
@if(count($activeFilters))
<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach($activeFilters as $key => $val)
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size:.78rem">
            {{ $val }}
            <a href="{{ request()->fullUrlWithQuery([$key => null]) }}" class="text-primary ms-1 text-decoration-none">×</a>
        </span>
    @endforeach
</div>
@endif

{{-- ── Results Table ────────────────────────────────────────── --}}
@if($items->isEmpty())
    <div class="page-card text-center py-5">
        <i class="bi bi-search fs-1 text-muted d-block mb-3" style="opacity:.3"></i>
        @if(request()->hasAny(['search','status','service_type_id','date_from','date_to']))
            <h5 class="text-muted">{{ __('No results match your filters') }}</h5>
            <p class="text-muted small">{{ __('Try adjusting your search or') }} <a href="{{ route('service-requests.index') }}">{{ __('clear all filters') }}</a>.</p>
        @else
            <h5 class="text-muted">{{ __('No service requests yet') }}</h5>
            <p class="text-muted small">{{ __('Create your first request to get started.') }}</p>
            <a href="{{ route('service-requests.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-lg me-1"></i>{{ __('New Request') }}
            </a>
        @endif
    </div>
@else
    <div class="bg-white rounded-3 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size:.9rem">
                <thead>
                    <tr class="bg-light border-bottom">
                        <th class="ps-3 text-muted fw-600" style="width:4%;font-size:.78rem">#</th>
                        <th class="text-muted fw-600" style="width:12%;font-size:.78rem">{{ __('REQ. NO') }}</th>
                        <th class="text-muted fw-600" style="font-size:.78rem">{{ __('TITLE / DETAILS') }}</th>
                        <th class="text-muted fw-600" style="width:13%;font-size:.78rem">{{ __('STATUS') }}</th>
                        @if($isAdmin)
                        <th class="text-muted fw-600" style="width:15%;font-size:.78rem">{{ __('PEOPLE') }}</th>
                        @endif
                        <th class="text-muted fw-600 pe-4" style="width:11%;font-size:.78rem">{{ __('ACTIONS') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        @php
                            $statusConfig = [
                                'New'          => ['bg-primary',   'New'],
                                'Under Review' => ['bg-info',      'Under Review'],
                                'Approved'     => ['bg-success',   'Approved'],
                                'Rejected'     => ['bg-danger',    'Rejected'],
                                'Completed'    => ['bg-secondary', 'Completed'],
                            ];
                            [$badgeClass, $badgeLabel] = $statusConfig[$item->status] ?? ['bg-light text-dark', $item->status];
                        @endphp
                        <tr class="align-middle border-bottom">
                            {{-- # --}}
                            <td class="ps-3 text-muted fw-600" style="font-size:.85rem">
                                {{ ($items->currentPage() - 1) * $items->perPage() + $loop->iteration }}
                            </td>
                            {{-- Request number --}}
                            <td>
                                <span class="font-monospace text-primary fw-semibold" style="font-size:.8rem;white-space:nowrap">
                                    {{ $item->request_number ?? '—' }}
                                </span>
                            </td>
                            {{-- Title + type + date stacked --}}
                            <td>
                                <a href="{{ route('service-requests.show', $item) }}"
                                   class="text-decoration-none text-dark fw-600 d-block" style="font-size:.92rem">
                                    {{ Str::limit($item->title, 60) }}
                                    @if($item->attachments_count ?? $item->attachment_path)
                                        <i class="bi bi-paperclip text-muted ms-1" style="font-size:.78rem" title="Has attachments"></i>
                                    @endif
                                </a>
                                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                    <span class="text-muted" style="font-size:.78rem">
                                        <i class="bi bi-grid me-1"></i>{{ $item->serviceType->name ?? '—' }}
                                    </span>
                                    <span class="text-muted" style="font-size:.78rem">
                                        <i class="bi bi-calendar3 me-1"></i>{{ $item->created_at->format('d M Y') }}
                                    </span>
                                </div>
                            </td>
                            {{-- Status --}}
                            <td>
                                <span class="badge badge-status {{ $badgeClass }}">{{ __($badgeLabel) }}</span>
                            </td>
                            {{-- People (admin only) --}}
                            @if($isAdmin)
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-muted" style="font-size:.8rem">
                                        <i class="bi bi-person me-1 opacity-50"></i>{{ $item->user->name ?? '—' }}
                                    </span>
                                    @if($item->assignedTo?->id)
                                    <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size:.72rem;width:fit-content">
                                        <i class="bi bi-person-check me-1"></i>{{ $item->assignedTo->name }}
                                    </span>
                                    @endif
                                </div>
                            </td>
                            @endif
                            {{-- Actions --}}
                            <td class="pe-4">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('service-requests.show', $item) }}"
                                       class="btn btn-outline-secondary btn-action btn-sm" title="{{ __('View') }}">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(auth()->user()->hasPermission('edit_request') || $item->user_id === auth()->id())
                                    <a href="{{ route('service-requests.edit', $item) }}"
                                       class="btn btn-outline-primary btn-action btn-sm" title="{{ __('Edit') }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('delete_request') || $item->user_id === auth()->id())
                                    <form action="{{ route('service-requests.destroy', $item) }}" method="POST"
                                          onsubmit="return confirm('{{ __('Move this request to trash?') }}');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-action btn-sm" title="{{ __('Delete') }}">
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

        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top bg-light flex-wrap gap-2">
            <div class="small text-muted">
                @if($items->hasPages())
                    {{ __('Showing') }} {{ $items->firstItem() }}–{{ $items->lastItem() }} {{ __('of') }} {{ $items->total() }} {{ __('requests') }}
                @else
                    {{ $items->total() }} {{ __('requests') }}
                @endif
            </div>
            @if($items->hasPages())
                {{ $items->links('pagination::bootstrap-5') }}
            @endif
        </div>
    </div>
@endif

@endsection
