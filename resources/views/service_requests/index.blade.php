@extends('layouts.app')
@section('title','Service Requests')

@section('content')

{{-- Stats Bar --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card card text-center py-3 px-2 bg-white">
            <div class="fs-2 fw-bold text-dark">{{ $stats['total'] }}</div>
            <div class="small text-muted mt-1">Total</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card card text-center py-3 px-2 bg-white">
            <div class="fs-2 fw-bold text-primary">{{ $stats['new'] }}</div>
            <div class="small text-muted mt-1">New</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card card text-center py-3 px-2 bg-white">
            <div class="fs-2 fw-bold text-info">{{ $stats['under_review'] }}</div>
            <div class="small text-muted mt-1">Under Review</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card card text-center py-3 px-2 bg-white">
            <div class="fs-2 fw-bold text-success">{{ $stats['approved'] }}</div>
            <div class="small text-muted mt-1">Approved</div>
        </div>
    </div>
    <div class="col-6 col-md-3 col-xl">
        <div class="stat-card card text-center py-3 px-2 bg-white">
            <div class="fs-2 fw-bold text-secondary">{{ $stats['completed'] }}</div>
            <div class="small text-muted mt-1">Completed</div>
        </div>
    </div>
</div>

{{-- Header + New Button --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h1 class="h4 fw-bold mb-0">Service Requests</h1>
        <p class="text-muted small mb-0">
            @if(request()->hasAny(['search','status','service_type_id','date_from','date_to']))
                Showing filtered results
            @else
                All active requests
            @endif
        </p>
    </div>
    @if(auth()->user()->hasPermission('create_request'))
    <a href="{{ route('service-requests.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Request
    </a>
    @endif
</div>

{{-- ── Filter Bar ──────────────────────────────────────────── --}}
<div class="page-card mb-4 py-3">
    <form method="GET" action="{{ route('service-requests.index') }}" class="row g-2 align-items-end">

        {{-- Search --}}
        <div class="col-12 col-md-4">
            <label class="form-label small text-muted mb-1">
                <i class="bi bi-search me-1"></i>Search
            </label>
            <input type="text" name="search" value="{{ request('search') }}"
                   class="form-control form-control-sm"
                   placeholder="Title, description, country…">
        </div>

        {{-- Status --}}
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                @foreach(['New','Under Review','Approved','Rejected','Completed'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>

        {{-- Service Type --}}
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1">Service Type</label>
            <select name="service_type_id" class="form-select form-select-sm">
                <option value="">All Types</option>
                @foreach($serviceTypes as $type)
                    <option value="{{ $type->id }}" {{ request('service_type_id') == $type->id ? 'selected' : '' }}>
                        {{ $type->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Date From --}}
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1">From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="form-control form-control-sm">
        </div>

        {{-- Date To --}}
        <div class="col-6 col-md-2">
            <label class="form-label small text-muted mb-1">To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="form-control form-control-sm">
        </div>

        {{-- Buttons --}}
        <div class="col-12 col-md-12 d-flex gap-2 mt-1">
            <button type="submit" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-funnel me-1"></i>Apply
            </button>
            @if(request()->hasAny(['search','status','service_type_id','date_from','date_to']))
                <a href="{{ route('service-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-1"></i>Clear Filters
                </a>
                <span class="text-muted small align-self-center ms-1">
                    {{ $items->total() }} {{ Str::plural('result', $items->total()) }} found
                </span>
            @endif
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
            <h5 class="text-muted">No results match your filters</h5>
            <p class="text-muted small">Try adjusting your search or <a href="{{ route('service-requests.index') }}">clear all filters</a>.</p>
        @else
            <h5 class="text-muted">No service requests yet</h5>
            <p class="text-muted small">Create your first request to get started.</p>
            <a href="{{ route('service-requests.create') }}" class="btn btn-primary mt-2">
                <i class="bi bi-plus-lg me-1"></i>New Request
            </a>
        @endif
    </div>
@else
    <div class="bg-white rounded-3 shadow-sm overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr class="bg-light border-bottom">
                        <th class="ps-4 text-muted fw-600 small" style="width:5%">#</th>
                        <th class="text-muted fw-600 small" style="width:28%">TITLE</th>
                        <th class="text-muted fw-600 small" style="width:12%">TYPE</th>
                        <th class="text-muted fw-600 small" style="width:13%">STATUS</th>
                        @if($isAdmin)
                        <th class="text-muted fw-600 small" style="width:13%">SUBMITTED BY</th>
                        @endif
                        <th class="text-muted fw-600 small" style="width:11%">DATE</th>
                        <th class="text-muted fw-600 small pe-4">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        @php
                            $rowNum = ($items->currentPage() - 1) * $items->perPage() + $loop->iteration;
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
                            <td class="ps-4 text-muted small">{{ $rowNum }}</td>
                            <td>
                                <a href="{{ route('service-requests.show', $item) }}"
                                   class="text-decoration-none text-dark fw-500">
                                    {{ Str::limit($item->title, 50) }}
                                </a>
                                @if($item->attachments_count ?? $item->attachment_path)
                                    <i class="bi bi-paperclip text-muted ms-1 small" title="Has attachments"></i>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted small">{{ $item->serviceType->name }}</span>
                            </td>
                            <td>
                                <span class="badge badge-status {{ $badgeClass }}">{{ $badgeLabel }}</span>
                            </td>
                            @if($isAdmin)
                            <td class="text-muted small">
                                <i class="bi bi-person me-1"></i>{{ $item->user->name }}
                            </td>
                            @endif
                            <td class="text-muted small">
                                {{ $item->created_at->format('d M Y') }}
                            </td>
                            <td class="pe-4">
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="{{ route('service-requests.show', $item) }}"
                                       class="btn btn-outline-secondary btn-action btn-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(auth()->user()->hasPermission('edit_request') || $item->user_id === auth()->id())
                                    <a href="{{ route('service-requests.edit', $item) }}"
                                       class="btn btn-outline-primary btn-action btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('delete_request') || $item->user_id === auth()->id())
                                    <form action="{{ route('service-requests.destroy', $item) }}" method="POST"
                                          onsubmit="return confirm('Move this request to trash?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-action btn-sm">
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
                    Showing {{ $items->firstItem() }}–{{ $items->lastItem() }} of {{ $items->total() }} requests
                @else
                    {{ $items->total() }} {{ Str::plural('request', $items->total()) }}
                @endif
            </div>
            @if($items->hasPages())
                {{ $items->links('pagination::bootstrap-5') }}
            @endif
        </div>
    </div>
@endif

@endsection
