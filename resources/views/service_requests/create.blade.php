@extends('layouts.app')

@section('title','Create Service Request')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white border-0 py-4">
                    <h2 class="mb-0">
                        <i class="bi bi-plus-circle me-2"></i>Create Service Request
                    </h2>
                </div>
                <div class="card-body p-4">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong><i class="bi bi-exclamation-triangle me-2"></i>Oops!</strong> Please fix the errors below.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('service-requests.store') }}" method="POST" enctype="multipart/form-data" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" 
                                placeholder="Enter service request title" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="invalid-feedback d-block">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" 
                                placeholder="Describe your service request..." rows="4" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback d-block">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                @php $statuses = ['New','Under Review','Approved','Rejected','Completed']; @endphp
                                @foreach($statuses as $s)
                                    <option value="{{ $s }}" {{ old('status', 'New') === $s ? 'selected' : '' }}>{{ $s }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <div class="invalid-feedback d-block">
                                    <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="attachment" class="form-label">Attachment (Optional)</label>
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

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Create Request
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
