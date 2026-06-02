@extends('layouts.app')

@section('title', __('Edit Page'))

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.pages.index') }}" class="text-muted text-decoration-none small">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Back to pages') }}
    </a>
    <h1 class="h4 fw-bold mt-2">{{ $page->title }}</h1>
</div>

<div class="row g-4">
    {{-- Page Settings --}}
    <div class="col-12 col-lg-4">
        <div class="page-card">
            <h6 class="fw-bold mb-3">{{ __('Page Settings') }}</h6>
            <form method="POST" action="{{ route('admin.pages.update', $page) }}">
                @csrf @method('PUT')
                <div class="mb-2">
                    <label class="form-label small">{{ __('Title') }}</label>
                    <input name="title" class="form-control form-control-sm" value="{{ $page->title }}" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">{{ __('Slug') }}</label>
                    <input name="slug" class="form-control form-control-sm" value="{{ $page->slug }}" required>
                </div>
                <div class="mb-2">
                    <label class="form-label small">{{ __('Meta Description') }}</label>
                    <textarea name="meta_description" class="form-control form-control-sm" rows="2">{{ $page->meta_description }}</textarea>
                </div>
                <div class="form-check mb-3">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" class="form-check-input" value="1" @checked($page->is_published) id="pub">
                    <label class="form-check-label small" for="pub">{{ __('Published') }}</label>
                </div>
                <button class="btn btn-primary btn-sm w-100">{{ __('Update Settings') }}</button>
            </form>
            <hr>
            <a href="/{{ $page->slug }}" target="_blank" class="btn btn-outline-secondary btn-sm w-100">
                <i class="bi bi-box-arrow-up-right me-1"></i>{{ __('Preview') }}
            </a>
        </div>
    </div>

    {{-- Sections --}}
    <div class="col-12 col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0">{{ __('Sections') }} ({{ $page->sections->count() }})</h6>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                <i class="bi bi-plus-lg me-1"></i>{{ __('Add Section') }}
            </button>
        </div>

        <div id="section-list" class="d-flex flex-column gap-2">
            @foreach($page->sections as $section)
            <div class="page-card d-flex align-items-center gap-3" data-id="{{ $section->id }}">
                <div class="text-muted" style="cursor:grab"><i class="bi bi-grip-vertical"></i></div>
                <div class="flex-grow-1">
                    <div class="fw-600 small">{{ ucfirst($section->type) }}</div>
                    @if($section->title)<div class="text-muted" style="font-size:.8rem">{{ $section->title }}</div>@endif
                </div>
                <div>
                    @if($section->is_visible)
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill small">{{ __('Visible') }}</span>
                    @else
                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill small">{{ __('Hidden') }}</span>
                    @endif
                </div>
                <a href="{{ route('admin.pages.section.edit', [$page, $section]) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i>
                </a>
                <form action="{{ route('admin.pages.section.destroy', [$page, $section]) }}" method="POST" class="d-inline"
                      onsubmit="return confirm('Delete this section?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Add Section Modal --}}
<div class="modal fade" id="addSectionModal" tabindex="-1">
    <div class="modal-dialog">
        <form class="modal-content" method="POST" action="{{ route('admin.pages.section.store', $page) }}">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title">{{ __('Add Section') }}</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small">{{ __('Section Type') }}</label>
                    <select name="type" class="form-select form-select-sm" required>
                        <option value="hero">{{ __('Hero (full-width banner)') }}</option>
                        <option value="about">{{ __('About / Text with Image') }}</option>
                        <option value="partners">{{ __('Partners / Cards Grid') }}</option>
                        <option value="contact">{{ __('Contact Form') }}</option>
                        <option value="text">{{ __('Text Block') }}</option>
                        <option value="image_text">{{ __('Image + Text') }}</option>
                        <option value="cards">{{ __('Cards Grid') }}</option>
                        <option value="html">{{ __('Raw HTML') }}</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">{{ __('Title (optional)') }}</label>
                    <input name="title" class="form-control form-control-sm">
                </div>
                <div class="mb-3">
                    <label class="form-label small">{{ __('Initial Content (JSON, optional)') }}</label>
                    <textarea name="content" class="form-control form-control-sm font-monospace" rows="4" placeholder='{"heading":"Hello"}'>{"heading":"","subheading":"","text":""}</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-primary">{{ __('Add Section') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const list = document.getElementById('section-list');
    if (!list) return;
    let dragSrcEl = null;
    list.querySelectorAll('[draggable]').forEach(el => el.draggable = false); // no native drag, use simple up/down
});
</script>
@endpush
