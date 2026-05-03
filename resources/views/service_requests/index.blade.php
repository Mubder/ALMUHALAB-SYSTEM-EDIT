@extends('layouts.app')

@section('title','Service Requests')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-0">Service Requests</h1>
            <p class="text-muted small mt-1">Manage and track all service requests</p>
        </div>
        <a href="{{ route('service-requests.create') }}" class="btn btn-primary btn-lg">
            <i class="bi bi-plus-circle me-2"></i>New Request
        </a>
    </div>

    @if($items->isEmpty())
        <div class="alert alert-info text-center py-5" role="alert">
            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
            <strong>No service requests yet.</strong>
            <p class="mb-0 mt-2">Create your first request to get started.</p>
        </div>
    @else
        <div class="table-responsive shadow-sm rounded">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4" style="width: 5%">#</th>
                        <th style="width: 30%">Title</th>
                        <th style="width: 20%">Status</th>
                        <th style="width: 15%">Created</th>
                        <th class="pe-4" style="width: 30%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr class="align-middle">
                            <td class="ps-4 fw-bold text-muted">{{ $item->id }}</td>
                            <td>
                                <a href="{{ route('service-requests.show', $item) }}" class="text-decoration-none fw-500">
                                    {{ Str::limit($item->title, 50) }}
                                </a>
                            </td>
                            <td>
                                @php
                                    $statusMap = [
                                        'New' => ['badge bg-primary', 'New'],
                                        'Under Review' => ['badge bg-info text-dark', 'Under Review'],
                                        'Approved' => ['badge bg-success', 'Approved'],
                                        'Rejected' => ['badge bg-danger', 'Rejected'],
                                        'Completed' => ['badge bg-secondary', 'Completed'],
                                        'open' => ['badge bg-primary', 'New'],
                                    ];
                                    $current = $statusMap[$item->status] ?? ['badge bg-light text-dark', $item->status];
                                @endphp
                                <span class="{{ $current[0] }}">{{ $current[1] }}</span>
                            </td>
                            <td class="text-muted small">{{ $item->created_at->format('M d, Y') }}</td>
                            <td class="pe-4">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('service-requests.show', $item) }}" class="btn btn-outline-secondary" title="View">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="{{ route('service-requests.edit', $item) }}" class="btn btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('service-requests.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this request?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
