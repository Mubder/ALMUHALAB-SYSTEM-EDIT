@extends('layouts.app')

@section('title','Service Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Service Requests</h1>
    <a href="{{ route('service-requests.create') }}" class="btn btn-primary">New Request</a>
</div>

<table class="table table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Title</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $item)
            <tr>
                <td>{{ $item->id }}</td>
                <td>{{ $item->title }}</td>
                <td>
                    @php
                        $map = [
                            'New' => 'badge bg-primary',
                            'Under Review' => 'badge bg-info text-dark',
                            'Approved' => 'badge bg-success',
                            'Rejected' => 'badge bg-danger',
                            'Completed' => 'badge bg-secondary',
                            'open' => 'badge bg-primary', // legacy
                        ];
                        $label = $item->status === 'open' ? 'New' : $item->status;
                    @endphp
                    <span class="{{ $map[$item->status] ?? 'badge bg-secondary' }}">{{ $label }}</span>
                </td>
                <td>{{ $item->created_at->format('Y-m-d') }}</td>
                <td>
                    <a href="{{ route('service-requests.show', $item) }}" class="btn btn-sm btn-outline-secondary">View</a>
                    <a href="{{ route('service-requests.edit', $item) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    <form action="{{ route('service-requests.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5">No requests found.</td></tr>
        @endforelse
    </tbody>
</table>

@endsection
