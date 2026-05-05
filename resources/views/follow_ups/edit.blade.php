@extends('layouts.app')
@section('title', 'Edit Step — ' . Str::limit($followUp->title, 40))

@section('content')
<div class="row justify-content-center">
<div class="col-lg-7">

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('service-requests.index') }}">Requests</a></li>
            <li class="breadcrumb-item">
                <a href="{{ route('service-requests.show', $serviceRequest) }}">{{ Str::limit($serviceRequest->title, 25) }}</a>
            </li>
            <li class="breadcrumb-item active">Edit Step</li>
        </ol>
    </nav>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>Please fix the errors below.</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="page-card">
        <h5 class="fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Timeline Step</h5>
        <p class="text-muted small mb-4">Update the step details. Changes are logged in the audit trail.</p>

        <form action="{{ route('follow-ups.update', [$serviceRequest, $followUp]) }}" method="POST">
            @csrf @method('PUT')

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label">Step Title <span class="text-danger">*</span></label>
                    <input type="text" name="title"
                           class="form-control @error('title') is-invalid @enderror"
                           value="{{ old('title', $followUp->title) }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Milestone <span class="text-danger">*</span></label>
                    <select name="status_type" class="form-select @error('status_type') is-invalid @enderror" required>
                        @foreach(\App\Models\MilestoneType::allActive() as $mt)
                            <option value="{{ $mt->key }}" {{ old('status_type', $followUp->status_type) === $mt->key ? 'selected' : '' }}>
                                {{ $mt->label }}
                            </option>
                        @endforeach
                    </select>
                    @error('status_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="4"
                              class="form-control @error('description') is-invalid @enderror"
                              placeholder="Describe this step clearly…">{{ old('description', $followUp->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Scheduled Date <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="date" name="scheduled_at"
                           class="form-control @error('scheduled_at') is-invalid @enderror"
                           value="{{ old('scheduled_at', $followUp->scheduled_at?->format('Y-m-d')) }}">
                    @error('scheduled_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="mb-2">
                        <div class="form-check">
                            <input type="checkbox" name="is_visible_to_client" id="vis_edit"
                                   class="form-check-input" value="1"
                                   {{ old('is_visible_to_client', $followUp->is_visible_to_client) ? 'checked' : '' }}>
                            <label class="form-check-label" for="vis_edit">Visible to client</label>
                        </div>
                        <div class="form-text">Uncheck to hide this step from the client's timeline.</div>
                    </div>
                </div>

                {{-- Status (completed toggle in edit) --}}
                <div class="col-12">
                    <div class="bg-light rounded-3 p-3 border d-flex align-items-center gap-3">
                        <div class="flex-grow-1">
                            <div class="fw-500 small">Step Completion</div>
                            <div class="text-muted" style="font-size:.8rem">
                                {{ $followUp->is_completed
                                    ? 'Completed on ' . ($followUp->completed_at?->format('d M Y H:i') ?? 'unknown date')
                                    : 'Not yet marked as completed.' }}
                            </div>
                        </div>
                        <form action="{{ route('follow-ups.toggle', [$serviceRequest, $followUp]) }}" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    class="btn btn-sm {{ $followUp->is_completed ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                                <i class="bi {{ $followUp->is_completed ? 'bi-arrow-counterclockwise' : 'bi-check-circle' }} me-1"></i>
                                {{ $followUp->is_completed ? 'Mark as Incomplete' : 'Mark as Complete' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-circle me-1"></i>Save Changes
                </button>
                <a href="{{ route('service-requests.show', $serviceRequest) }}" class="btn btn-outline-secondary">
                    Cancel
                </a>
            </div>
        </form>
    </div>

</div>
</div>
@endsection
