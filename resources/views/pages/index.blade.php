@extends('layouts.app')

@section('title', __('Page Builder'))

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h4 fw-bold mb-0"><i class="bi bi-file-earmark-text text-primary me-2"></i>{{ __('Page Builder') }}</h1>
        <p class="text-muted small mb-0">{{ __('Manage landing pages and their sections') }}</p>
    </div>
    <a href="{{ route('admin.pages.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>{{ __('New Page') }}
    </a>
</div>

<div class="page-card p-0">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>{{ __('Title') }}</th>
                    <th>{{ __('Slug') }}</th>
                    <th>{{ __('Sections') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Updated') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                <tr>
                    <td class="fw-600">{{ $page->title }}</td>
                    <td><code>{{ $page->slug }}</code></td>
                    <td>{{ $page->sections->count() }}</td>
                    <td>
                        @if($page->is_published)
                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill">{{ __('Published') }}</span>
                        @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill">{{ __('Draft') }}</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ $page->updated_at->diffForHumans() }}</td>
                    <td class="text-end">
                        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form action="{{ route('admin.pages.destroy', $page) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this page?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">{{ __('No pages yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<a href="/" target="_blank" class="btn btn-outline-secondary btn-sm mt-3">
    <i class="bi bi-box-arrow-up-right me-1"></i>{{ __('View landing page') }}
</a>

@endsection
