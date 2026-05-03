@extends('layouts.app')

@section('title','Create Service Request')

@section('content')
<div class="row">
    <div class="col-md-8">
        <h2>Create Service Request</h2>
        <form action="{{ route('service-requests.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" name="title" class="form-control" value="{{ old('title') }}">
                @error('title')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                @error('description')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Attachment</label>
                <input type="file" name="attachment" class="form-control">
                @error('attachment')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    @php $statuses = ['New','Under Review','Approved','Rejected','Completed']; @endphp
                    @foreach($statuses as $s)
                        <option value="{{ $s }}" {{ old('status', 'New') === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
                @error('status')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <button class="btn btn-primary">Create</button>
            <a href="{{ route('service-requests.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
