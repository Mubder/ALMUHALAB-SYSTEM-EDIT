@extends('layouts.app')

@section('title', __('Edit Section'))

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.pages.edit', $page) }}" class="text-muted text-decoration-none small">
        <i class="bi bi-arrow-left me-1"></i>{{ __('Back to page') }}
    </a>
    <h1 class="h4 fw-bold mt-2">{{ ucfirst($section->type) }}: {{ $section->title ?? __('Untitled') }}</h1>
</div>

<div class="page-card">
    <form method="POST" action="{{ route('admin.pages.section.update', [$page, $section]) }}" enctype="multipart/form-data">
        @csrf @method('PUT')

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">{{ __('Type') }}</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="hero" @selected($section->type=='hero')>{{ __('Hero Banner') }}</option>
                    <option value="about" @selected($section->type=='about')>{{ __('About / Text + Image') }}</option>
                    <option value="partners" @selected($section->type=='partners')>{{ __('Partners Grid') }}</option>
                    <option value="contact" @selected($section->type=='contact')>{{ __('Contact') }}</option>
                    <option value="text" @selected($section->type=='text')>{{ __('Text Block') }}</option>
                    <option value="image_text" @selected($section->type=='image_text')>{{ __('Image + Text') }}</option>
                    <option value="cards" @selected($section->type=='cards')>{{ __('Cards Grid') }}</option>
                    <option value="html" @selected($section->type=='html')>{{ __('Raw HTML') }}</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label small">{{ __('Title') }}</label>
                <input name="title" class="form-control form-control-sm" value="{{ $section->title }}">
            </div>
        </div>

        <hr>

        @php $c = $section->content ?? []; @endphp

        {{-- Hero --}}
        @if($section->type === 'hero')
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label small">{{ __('Heading') }}</label>
                <input name="heading" class="form-control form-control-sm" value="{{ $c['heading'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label small">{{ __('Subheading') }}</label>
                <input name="subheading" class="form-control form-control-sm" value="{{ $c['subheading'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label small">{{ __('Tagline') }}</label>
                <input name="tagline" class="form-control form-control-sm" value="{{ $c['tagline'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label small">{{ __('Button Text') }}</label>
                <input name="button_text" class="form-control form-control-sm" value="{{ $c['button_text'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label small">{{ __('Button URL') }}</label>
                <input name="button_url" class="form-control form-control-sm" value="{{ $c['button_url'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label small">{{ __('Logo Image') }}</label>
                <input type="file" name="logo" class="form-control form-control-sm" accept="image/*">
                @if(!empty($c['logo']))
                <div class="mt-1 d-flex align-items-center gap-2">
                    <img src="{{ Storage::url($c['logo']) }}" style="max-height:50px" alt="">
                    <label class="small"><input type="checkbox" name="remove_logo" value="1"> {{ __('Remove') }}</label>
                </div>
                @endif
            </div>
        </div>

        {{-- About --}}
        @elseif($section->type === 'about')
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label small">{{ __('Heading') }}</label>
                <input name="heading" class="form-control form-control-sm" value="{{ $c['heading'] ?? '' }}">
            </div>
            <div class="col-12">
                <label class="form-label small">{{ __('Text') }}</label>
                <textarea name="text" class="form-control form-control-sm" rows="5">{{ $c['text'] ?? '' }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label small">{{ __('Image') }}</label>
                <input type="file" name="image" class="form-control form-control-sm">
                @if(!empty($c['image']))
                    <div class="mt-1"><img src="{{ Storage::url($c['image']) }}" style="max-height:80px"></div>
                @endif
            </div>
        </div>

        {{-- Partners --}}
        @elseif($section->type === 'partners')
        <div class="mb-3">
            <label class="form-label small">{{ __('Heading') }}</label>
            <input name="heading" class="form-control form-control-sm" value="{{ $c['heading'] ?? 'WE REPRESENT THE FOLLOWING' }}">
        </div>
        <div id="partners-container">
            @forelse($c['partners'] ?? [] as $i => $partner)
            <div class="partner-card-editor card mb-2 p-3">
                <div class="row g-2 align-items-start">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">{{ __('Name') }}</label>
                        <input name="partners[{{ $i }}][name]" class="form-control form-control-sm" value="{{ $partner['name'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">{{ __('URL') }}</label>
                        <input name="partners[{{ $i }}][url]" class="form-control form-control-sm" value="{{ $partner['url'] ?? '' }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">{{ __('Logo') }}</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="file" name="partners[{{ $i }}][logo]" class="form-control form-control-sm" accept="image/*">
                            @if(!empty($partner['logo']))
                            <a href="{{ Storage::url($partner['logo']) }}" target="_blank" class="small text-nowrap">
                                <img src="{{ Storage::url($partner['logo']) }}" style="max-height:28px" alt="">
                            </a>
                            <label class="small text-nowrap">
                                <input type="checkbox" name="partners[{{ $i }}][remove_logo]" value="1">
                                {{ __('Remove') }}
                            </label>
                            @endif
                        </div>
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label small text-muted">{{ __('Description') }}</label>
                        <textarea name="partners[{{ $i }}][description]" class="form-control form-control-sm" rows="2">{{ $partner['description'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-muted small py-2">{{ __('No partners yet. Click "Add Partner" below.') }}</div>
            @endforelse
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm mb-3" onclick="addPartner()">
            <i class="bi bi-plus-circle"></i> {{ __('Add Partner') }}
        </button>
        <template id="partner-template">
            <div class="partner-card-editor card mb-2 p-3">
                <div class="row g-2 align-items-start">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">{{ __('Name') }}</label>
                        <input name="partners[__INDEX__][name]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">{{ __('URL') }}</label>
                        <input name="partners[__INDEX__][url]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">{{ __('Logo') }}</label>
                        <input type="file" name="partners[__INDEX__][logo]" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div class="col-12 mt-2">
                        <label class="form-label small text-muted">{{ __('Description') }}</label>
                        <textarea name="partners[__INDEX__][description]" class="form-control form-control-sm" rows="2"></textarea>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm mt-2" onclick="this.closest('.partner-card-editor').remove()">
                    <i class="bi bi-trash"></i> {{ __('Remove') }}
                </button>
            </div>
        </template>
        <script>
        let partnerIndex = {{ count($c['partners'] ?? []) }};
        function addPartner() {
            const html = document.getElementById('partner-template').innerHTML.replace(/__INDEX__/g, partnerIndex++);
            document.getElementById('partners-container').insertAdjacentHTML('beforeend', html);
        }
        </script>

        {{-- Contact --}}
        @elseif($section->type === 'contact')
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label small">{{ __('Heading') }}</label>
                <input name="heading" class="form-control form-control-sm" value="{{ $c['heading'] ?? 'Contact Us' }}">
            </div>
            <div class="col-12">
                <label class="form-label small">{{ __('Intro Text') }}</label>
                <textarea name="intro" class="form-control form-control-sm" rows="2">{{ $c['intro'] ?? '' }}</textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label small">{{ __('Address') }}</label>
                <input name="address" class="form-control form-control-sm" value="{{ $c['address'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small">{{ __('Phone') }}</label>
                <input name="phone" class="form-control form-control-sm" value="{{ $c['phone'] ?? '' }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small">{{ __('Email') }}</label>
                <input name="email" class="form-control form-control-sm" value="{{ $c['email'] ?? '' }}">
            </div>
        </div>

        {{-- Text --}}
        @elseif($section->type === 'text')
        <div class="mb-3">
            <label class="form-label small">{{ __('Content') }}</label>
            <textarea name="text" class="form-control form-control-sm" rows="8">{{ $c['text'] ?? '' }}</textarea>
        </div>

        {{-- Image + Text --}}
        @elseif($section->type === 'image_text')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label small">{{ __('Image') }}</label>
                <input type="file" name="image" class="form-control form-control-sm">
                @if(!empty($c['image']))
                    <div class="mt-1"><img src="{{ Storage::url($c['image']) }}" style="max-height:80px"></div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label small">{{ __('Image Position') }}</label>
                <select name="image_position" class="form-select form-select-sm">
                    <option value="left" @selected(($c['image_position']??'left')=='left')>{{ __('Left') }}</option>
                    <option value="right" @selected(($c['image_position']??'')=='right')>{{ __('Right') }}</option>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label small">{{ __('Text') }}</label>
                <textarea name="text" class="form-control form-control-sm" rows="5">{{ $c['text'] ?? '' }}</textarea>
            </div>
        </div>

        {{-- Cards --}}
        @elseif($section->type === 'cards')
        <div class="mb-3">
            <label class="form-label small">{{ __('Heading') }}</label>
            <input name="heading" class="form-control form-control-sm" value="{{ $c['heading'] ?? '' }}">
        </div>
        <div class="mb-3">
            <label class="form-label small">{{ __('Cards (JSON array)') }}</label>
            <textarea name="cards" class="form-control form-control-sm font-monospace" rows="10">{{ json_encode($c['cards'] ?? [], JSON_PRETTY_PRINT) }}</textarea>
            <div class="form-text small">{{ __('Each card: {"title":"","text":"","icon":"","image":""}') }}</div>
        </div>

        {{-- HTML --}}
        @elseif($section->type === 'html')
        <div class="mb-3">
            <label class="form-label small">{{ __('Raw HTML') }}</label>
            <textarea name="html" class="form-control form-control-sm font-monospace" rows="12">{{ $c['html'] ?? '' }}</textarea>
        </div>
        @endif

        <hr>

        <div class="form-check mb-3">
            <input type="hidden" name="is_visible" value="0">
            <input type="checkbox" name="is_visible" class="form-check-input" value="1" @checked($section->is_visible) id="vis">
            <label class="form-check-label small" for="vis">{{ __('Visible') }}</label>
        </div>

        <button class="btn btn-primary btn-sm">{{ __('Update Section') }}</button>
        <a href="{{ route('admin.pages.edit', $page) }}" class="btn btn-outline-secondary btn-sm">{{ __('Cancel') }}</a>
    </form>
</div>
@endsection
