@extends('layouts.app')

@section('title','Edit Service Request')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white border-0 py-4">
                    <h2 class="mb-0">
                        <i class="bi bi-pencil-square me-2"></i>Edit Service Request
                    </h2>
                </div>
                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong><i class="bi bi-exclamation-triangle me-2"></i>Oops!</strong> Please fix the errors below.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('service-requests.update', $serviceRequest) }}" method="POST" enctype="multipart/form-data" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" 
                                value="{{ old('title', $serviceRequest->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback d-block">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4" required>{{ old('description', $serviceRequest->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            @php
                                $statuses = ['New','Under Review','Approved','Rejected','Completed'];
                                $current = old('status', $serviceRequest->status === 'open' ? 'New' : $serviceRequest->status);
                            @endphp
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                @foreach($statuses as $s)
                                    <option value="{{ $s }}" {{ $current === $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback d-block">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <fieldset class="border rounded p-3 mb-3 bg-light">
                                <legend class="float-none w-auto px-2">
                                    <i class="bi bi-paperclip me-1"></i>Attachment
                                </legend>
                                @if($serviceRequest->attachment_path)
                                    <div class="alert alert-info mb-3 mt-2">
                                        <i class="bi bi-file-earmark me-2"></i>
                                        Current: <a href="{{ asset('storage/'.$serviceRequest->attachment_path) }}" target="_blank" class="alert-link">Download</a>
                                    </div>
                                @else
                                    <div class="alert alert-secondary mb-3 mt-2">
                                        <i class="bi bi-inbox me-2"></i>No attachment
                                    </div>
                                @endif
                                
                                <div>
                                    <label for="attachment" class="form-label">Replace Attachment (Optional)</label>
                                    <input type="file" class="form-control @error('attachment') is-invalid @enderror" id="attachment" name="attachment">
                                    <small class="form-text text-muted d-block mt-1">
                                        <i class="bi bi-info-circle me-1"></i>Max file size: 5MB
                                    </small>
                                    @error('attachment')
                                        <div class="invalid-feedback d-block">
                                            <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </fieldset>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Save Changes
                            </button>
                            <a href="{{ route('service-requests.index') }}" class="btn btn-secondary btn-lg">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
