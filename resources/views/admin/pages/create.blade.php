@extends('layouts.app')

@section('title', __('New Page'))

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.pages.index') }}" class="text-muted text-decoration-none small">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Back to pages') }}
    </a>
    <h1 class="h4 fw-bold mt-2">{{ __('New Page') }}</h1>
</div>

<div class="page-card">
    <form method="POST" action="{{ route('admin.pages.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">{{ __('Page Title') }}</label>
            <input name="title" class="form-control" required maxlength="255"
                   oninput="document.getElementById('slug-preview').textContent = this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)/g,'')">
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Slug') }}</label>
            <input name="slug" class="form-control" required maxlength="255" pattern="[a-z0-9-]+">
            <div class="form-text text-muted small">https://example.com/<span id="slug-preview" class="text-primary">home</span></div>
        </div>
        <div class="mb-3">
            <label class="form-label">{{ __('Meta Description') }}</label>
            <textarea name="meta_description" class="form-control" rows="2" maxlength="500"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">{{ __('Create Page') }}</button>
    </form>
</div>
@endsection
