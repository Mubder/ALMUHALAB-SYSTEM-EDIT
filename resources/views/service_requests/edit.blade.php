@extends('layouts.app')

@section('title','Edit Service Request')

@section('content')
<div class="row">
    <div class="col-md-8">
        <h2>Edit Service Request</h2>
        <form action="{{ route('service-requests.update', $serviceRequest) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $serviceRequest->title) }}">
                @error('title')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4">{{ old('description', $serviceRequest->description) }}</textarea>
                @error('description')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Current Attachment</label>
                @if($serviceRequest->attachment_path)
                    <div><a href="{{ asset('storage/'.$serviceRequest->attachment_path) }}" target="_blank">Download</a></div>
                @else
                    <div>No attachment</div>
                @endif
            </div>

            <div class="mb-3">
                <label class="form-label">Replace Attachment</label>
                <input type="file" name="attachment" class="form-control">
                @error('attachment')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                @php
                    $statuses = ['New','Under Review','Approved','Rejected','Completed'];
                    $current = old('status', $serviceRequest->status === 'open' ? 'New' : $serviceRequest->status);
                @endphp
                <select name="status" class="form-select">
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" {{ $current === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
                @error('status')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <button class="btn btn-primary">Save</button>
            <a href="{{ route('service-requests.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
