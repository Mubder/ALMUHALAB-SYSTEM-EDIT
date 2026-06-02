<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $page->meta_description ?? $page->title }}">
    <title>{{ $page->title }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #3e597d;
            --primary-light: #5a7a9e;
            --accent: #b8860b;
            --dark: #161616;
            --font-base: 'Inter', system-ui, sans-serif;
            --font-display: 'Cairo', 'Inter', system-ui, sans-serif;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: var(--font-base); color: #fff; background: var(--dark); line-height: 1.6; }
        a { color: var(--accent); text-decoration: none; }
        a:hover { opacity: .85; }
        img { max-width: 100%; height: auto; }

        .lp-nav {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            padding: .75rem 2rem;
            display: flex; align-items: center; justify-content: space-between;
            background: rgba(22,22,22,.92); backdrop-filter: blur(8px);
            border-bottom: 1px solid rgba(255,255,255,.06);
        }
        .lp-nav .brand img { height: 44px; }
        .lp-nav .brand { display: flex; align-items: center; gap: .75rem; color: #fff; font-weight: 700; font-size: 1.1rem; }
        .lp-nav .nav-links { display: flex; align-items: center; gap: 1.5rem; }
        .lp-nav .nav-links a { color: rgba(255,255,255,.65); font-size: .85rem; font-weight: 500; transition: color .2s; }
        .lp-nav .nav-links a:hover { color: #fff; }
        .lp-nav .btn-app { background: var(--primary); color: #fff; border: none; padding: .4rem 1.2rem; border-radius: 6px; font-size: .82rem; font-weight: 600; }

        .lp-section { padding: 5rem 2rem; max-width: 1200px; margin: 0 auto; }
        .lp-section-wide { padding: 5rem 2rem; }
        .lp-section-dark { background: #111; }
        .lp-section h2 { font-size: 2rem; font-weight: 700; margin-bottom: 1rem; color: #fff; }
        .lp-section .subtitle { color: rgba(255,255,255,.5); font-size: .9rem; margin-bottom: 2.5rem; max-width: 600px; }

        .hero-section {
            min-height: 85vh;
            display: flex; align-items: center; justify-content: center;
            text-align: center;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            position: relative; overflow: hidden;
            padding: 6rem 2rem 4rem;
        }
        .hero-section::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse 60% 50% at 50% 40%, rgba(62,89,125,.15) 0%, transparent 70%);
        }
        .hero-content { position: relative; z-index: 1; max-width: 800px; }
        .hero-logo { max-height: 120px; margin-bottom: 1.5rem; }
        .hero-section h1 { font-size: clamp(2rem, 5vw, 3.5rem); font-weight: 700; margin-bottom: .5rem; }
        .hero-section .tagline { color: var(--accent); font-size: 1rem; font-weight: 600; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 1.5rem; }
        .hero-section p { color: rgba(255,255,255,.6); font-size: 1.1rem; max-width: 600px; margin: 0 auto 2rem; }
        .hero-btn { display: inline-block; background: var(--primary); color: #fff; padding: .75rem 2.5rem; border-radius: 50px; font-weight: 600; border: 2px solid var(--primary); transition: all .3s; }
        .hero-btn:hover { background: transparent; color: #fff; }

        .about-section { display: flex; gap: 3rem; align-items: center; flex-wrap: wrap; }
        .about-section .about-text { flex: 1; min-width: 280px; }
        .about-section .about-text h2 { font-size: 1.75rem; }
        .about-section .about-text p { color: rgba(255,255,255,.7); line-height: 1.8; }
        .about-section .about-image { flex: 1; min-width: 280px; border-radius: 12px; overflow: hidden; }
        .about-section .about-image img { width: 100%; display: block; }

        .partners-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
        .partner-card {
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px; padding: 1.5rem;
            transition: background .3s, transform .2s;
        }
        .partner-card:hover { background: rgba(255,255,255,.08); transform: translateY(-2px); }
        .partner-card h5 { font-size: .95rem; font-weight: 600; margin-bottom: .5rem; }
        .partner-card p { font-size: .82rem; color: rgba(255,255,255,.55); line-height: 1.6; }
        .partner-card .partner-logo { max-height: 40px; margin-bottom: .75rem; opacity: .8; }

        .contact-section { text-align: center; }
        .contact-info { display: flex; flex-wrap: wrap; gap: 2rem; justify-content: center; margin: 2rem 0; }
        .contact-info .info-item { flex: 1; min-width: 200px; }
        .contact-info .info-item i { font-size: 1.5rem; color: var(--accent); margin-bottom: .5rem; }
        .contact-info .info-item .label { color: rgba(255,255,255,.4); font-size: .78rem; }
        .contact-info .info-item .value { color: #fff; font-weight: 500; }

        .footer-bar {
            text-align: center; padding: 2rem;
            border-top: 1px solid rgba(255,255,255,.06);
            font-size: .8rem; color: rgba(255,255,255,.3);
        }

        .text-section p { color: rgba(255,255,255,.7); line-height: 1.8; max-width: 800px; margin: 0 auto; }
        .image-text-section { display: flex; gap: 3rem; align-items: center; flex-wrap: wrap; }
        .image-text-section .it-image { flex: 1; min-width: 250px; border-radius: 12px; overflow: hidden; }
        .image-text-section .it-text { flex: 1; min-width: 250px; }
        .image-text-section .it-text p { color: rgba(255,255,255,.7); }

        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem; }
        .card-item {
            background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px; padding: 1.5rem; text-align: center;
        }
        .card-item i { font-size: 2rem; color: var(--accent); margin-bottom: .75rem; }
        .card-item h5 { font-size: 1rem; margin-bottom: .5rem; }

        @media (max-width: 768px) {
            .lp-nav { padding: .5rem 1rem; }
            .lp-nav .nav-links { display: none; }
            .lp-section { padding: 3rem 1rem; }
            .about-section { flex-direction: column; }
        }
    </style>
</head>
<body>

<nav class="lp-nav">
    <a href="/" class="brand">
        <span>ALMuhalab</span>
    </a>
    <div class="nav-links">
        <a href="#about">{{ __('About') }}</a>
        <a href="#partners">{{ __('Partners') }}</a>
        <a href="#contact">{{ __('Contact') }}</a>
        <a href="{{ route('login') }}" class="btn-app">{{ __('App Login') }}</a>
    </div>
</nav>

@foreach($page->visibleSections as $section)
    @php $c = $section->content ?? []; @endphp

    @if($section->type === 'hero')
    <div class="hero-section">
        <div class="hero-content">
            @if(!empty($c['logo']))
                <img src="{{ Storage::url($c['logo']) }}" class="hero-logo" alt="Logo">
            @endif
            @if(!empty($c['tagline']))
                <div class="tagline">{{ $c['tagline'] }}</div>
            @endif
            <h1>{{ $c['heading'] ?? $page->title }}</h1>
            @if(!empty($c['subheading']))
                <p>{{ $c['subheading'] }}</p>
            @endif
            @if(!empty($c['button_text']))
                <a href="{{ $c['button_url'] ?? '#contact' }}" class="hero-btn">{{ $c['button_text'] }}</a>
            @endif
        </div>
    </div>

    @elseif($section->type === 'about')
    <div class="lp-section-wide lp-section-dark" id="about">
        <div class="lp-section about-section">
            @if(!empty($c['image']))
            <div class="about-image">
                <img src="{{ Storage::url($c['image']) }}" alt="{{ $c['heading'] ?? '' }}">
            </div>
            @endif
            <div class="about-text">
                <h2>{{ $c['heading'] ?? $section->title }}</h2>
                <p>{!! nl2br(e($c['text'] ?? '')) !!}</p>
            </div>
        </div>
    </div>

    @elseif($section->type === 'partners')
    <div class="lp-section-wide" id="partners">
        <div class="lp-section">
            <h2 class="text-center">{{ $c['heading'] ?? 'Our Partners' }}</h2>
            @if(!empty($c['partners']))
            <div class="partners-grid mt-4">
                @foreach($c['partners'] as $partner)
                <div class="partner-card">
                    @if(!empty($partner['logo']))
                        <img src="{{ $partner['logo'] }}" class="partner-logo" alt="">
                    @endif
                    <h5>{{ $partner['name'] ?? '' }}</h5>
                    <p>{{ $partner['description'] ?? '' }}</p>
                    @if(!empty($partner['url']))
                        <a href="{{ $partner['url'] }}" target="_blank" class="small">{{ __('Learn more') }} →</a>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    @elseif($section->type === 'contact')
    <div class="lp-section-wide lp-section-dark" id="contact">
        <div class="lp-section contact-section">
            <h2>{{ $c['heading'] ?? 'Contact Us' }}</h2>
            @if(!empty($c['intro']))
                <p class="subtitle mx-auto">{{ $c['intro'] }}</p>
            @endif
            <div class="contact-info">
                @if(!empty($c['address']))
                <div class="info-item">
                    <i class="bi bi-geo-alt"></i>
                    <div class="label">{{ __('Address') }}</div>
                    <div class="value">{{ $c['address'] }}</div>
                </div>
                @endif
                @if(!empty($c['phone']))
                <div class="info-item">
                    <i class="bi bi-telephone"></i>
                    <div class="label">{{ __('Phone') }}</div>
                    <div class="value"><a href="tel:{{ $c['phone'] }}">{{ $c['phone'] }}</a></div>
                </div>
                @endif
                @if(!empty($c['email']))
                <div class="info-item">
                    <i class="bi bi-envelope"></i>
                    <div class="label">{{ __('Email') }}</div>
                    <div class="value"><a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></div>
                </div>
                @endif
            </div>
        </div>
    </div>

    @elseif($section->type === 'text')
    <div class="lp-section-wide">
        <div class="lp-section text-section">
            @if($section->title)<h2 class="text-center">{{ $section->title }}</h2>@endif
            <p>{!! nl2br(e($c['text'] ?? '')) !!}</p>
        </div>
    </div>

    @elseif($section->type === 'image_text')
    <div class="lp-section-wide {{ $loop->even ? 'lp-section-dark' : '' }}">
        <div class="lp-section image-text-section">
            @if(!empty($c['image']) && ($c['image_position'] ?? 'left') === 'left')
            <div class="it-image"><img src="{{ Storage::url($c['image']) }}" alt=""></div>
            @endif
            <div class="it-text">
                @if($section->title)<h2>{{ $section->title }}</h2>@endif
                <p>{!! nl2br(e($c['text'] ?? '')) !!}</p>
            </div>
            @if(!empty($c['image']) && ($c['image_position'] ?? 'left') === 'right')
            <div class="it-image"><img src="{{ Storage::url($c['image']) }}" alt=""></div>
            @endif
        </div>
    </div>

    @elseif($section->type === 'cards')
    <div class="lp-section-wide">
        <div class="lp-section">
            @if(!empty($c['heading']))<h2 class="text-center">{{ $c['heading'] }}</h2>@endif
            @if(!empty($c['cards']))
            <div class="cards-grid mt-4">
                @foreach($c['cards'] as $card)
                <div class="card-item">
                    @if(!empty($card['icon']))<i class="bi {{ $card['icon'] }}"></i>@endif
                    @if(!empty($card['image']))<img src="{{ $card['image'] }}" alt="" style="max-height:60px;margin-bottom:.75rem">@endif
                    <h5>{{ $card['title'] ?? '' }}</h5>
                    <p class="small text-muted">{{ $card['text'] ?? '' }}</p>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    @elseif($section->type === 'html')
    <div class="lp-section-wide">
        <div class="lp-section">
            {!! $c['html'] ?? '' !!}
        </div>
    </div>
    @endif
@endforeach

<div class="footer-bar">
    &copy; {{ date('Y') }} ALMuhalab International Co. — All Rights Reserved.
</div>

</body>
</html>
