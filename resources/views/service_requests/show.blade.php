@extends('layouts.app')

@section('title','Service Request Details')

@section('content')
<div class="row">
    <div class="col-md-8">
        <h2>{{ $serviceRequest->title }}</h2>
        <p><strong>Status:</strong>
            @php
                $map = [
                    'New' => 'badge bg-primary',
                    'Under Review' => 'badge bg-info text-dark',
                    'Approved' => 'badge bg-success',
                    'Rejected' => 'badge bg-danger',
                    'Completed' => 'badge bg-secondary',
                    'open' => 'badge bg-primary', // legacy
                ];
                $label = $serviceRequest->status === 'open' ? 'New' : $serviceRequest->status;
            @endphp
            <span class="{{ $map[$serviceRequest->status] ?? 'badge bg-secondary' }}">{{ $label }}</span>
        </p>
        <p><strong>Created:</strong> {{ $serviceRequest->created_at->format('Y-m-d H:i') }}</p>
        <hr>
        <h5>Description</h5>
        <p>{{ $serviceRequest->description }}</p>

        <h5>Attachment</h5>
        @if($serviceRequest->attachment_path)
            <a href="{{ asset('storage/'.$serviceRequest->attachment_path) }}" class="btn btn-sm btn-outline-primary" target="_blank">Download Attachment</a>
        @else
            <div>No attachment</div>
        @endif

        <hr>
        <a href="{{ route('service-requests.edit', $serviceRequest) }}" class="btn btn-primary">Edit</a>
        <a href="{{ route('service-requests.index') }}" class="btn btn-secondary">Back</a>
    </div>
</div>
@endsection
