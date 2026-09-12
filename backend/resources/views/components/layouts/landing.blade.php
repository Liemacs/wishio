@props(['title' => null, 'event' => null])

@php
    $locale   = app()->getLocale();
    $ogLocale = ['ro' => 'ro_MD', 'ru' => 'ru_MD', 'en' => 'en_US'][$locale] ?? 'ro_MD';
@endphp

<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#e11d48">

    <title>{{ $title ?? __('landing.meta.title') }}</title>
    <meta name="description" content="{{ __('landing.meta.description') }}">

    {{-- Faza 0: nu vrem inca indexare. Vezi docs/14 § 6. --}}
    <meta name="robots" content="noindex">

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon-32.png" sizes="32x32">
    <link rel="apple-touch-icon" href="/favicon-180.png">

    {{-- Previzualizarea linkului conteaza direct pentru distributie (docs/14 § 4):
         fara ea, linkul postat intr-un grup arata rupt. Imagine separata pe limba. --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Wishio">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title ?? __('landing.meta.title') }}">
    <meta property="og:description" content="{{ __('landing.meta.description') }}">
    <meta property="og:image" content="{{ url("/og/$locale.png") }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="{{ $ogLocale }}">
    <meta name="twitter:card" content="summary_large_image">

    @foreach (config('wishio.locales.supported') as $alt)
        <link rel="alternate" hreflang="{{ $alt }}" href="{{ route('locale.switch', $alt) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">

    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-surface-50 text-surface-900 antialiased">

    <header class="mx-auto flex max-w-3xl items-center justify-between px-5 py-5">
        <a href="{{ route('landing') }}" class="flex items-center gap-2 text-lg font-bold tracking-tight">
            <img src="/favicon.svg" alt="" width="26" height="26" class="rounded-lg">
            Wishio
        </a>

        <nav class="flex gap-1 rounded-full bg-surface-200/70 p-1">
            @foreach (config('wishio.locales.supported') as $code)
                <a href="{{ route('locale.switch', $code) }}" data-locale="{{ $code }}"
                   class="rounded-full px-3 py-1 text-xs font-semibold uppercase transition
                          {{ $locale === $code
                             ? 'bg-white text-surface-900 shadow-sm'
                             : 'text-surface-500 hover:text-surface-800' }}">
                    {{ $code }}
                </a>
            @endforeach
        </nav>
    </header>

    <main class="mx-auto max-w-3xl px-5 pb-20">
        {{ $slot }}
    </main>

    <footer class="mx-auto max-w-3xl px-5 pb-10 text-center text-xs leading-relaxed text-surface-400">
        {{ __('landing.footer.about') }}
    </footer>

    {{-- Analytics. Schema de evenimente: docs/07-metrici.md § 3.
         Se incarca doar daca exista cheia; pagina functioneaza identic fara ea. --}}
    @if (config('services.posthog.key'))
        <script>
            !function(t,e){var o,n,p,r;e.__SV||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.async=!0,p.src=s.api_host+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="capture identify alias people.set people.set_once set_config register register_once unregister opt_out_capturing has_opted_out_capturing opt_in_capturing reset isFeatureEnabled onFeatureFlags getFeatureFlag getFeatureFlagPayload reloadFeatureFlags group updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures getActiveMatchingSurveys getSurveys".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
            posthog.init(@json(config('services.posthog.key')), {
                api_host: @json(config('services.posthog.host')),
                persistence: 'localStorage',
                autocapture: false,
            });
        </script>
    @endif

    <script>
        (function () {
            var track = function (name, props) {
                if (window.posthog) { window.posthog.capture(name, props || {}); }
            };

            var locale = @json($locale);
            var source = new URLSearchParams(location.search).get('src');

            @if ($event)
                track(@json($event), { locale: locale });
            @else
                track('landing_viewed', { locale: locale, source: source });
            @endif

            document.querySelectorAll('[data-locale]').forEach(function (link) {
                link.addEventListener('click', function () {
                    track('language_selected', { from: locale, to: link.dataset.locale });
                });
            });

            // Un singur eveniment la prima interactiune cu formularul:
            // masoara cati incep sa completeze fata de cati trimit.
            var form = document.querySelector('form[data-track-form]');
            if (form) {
                var started = false;
                form.addEventListener('input', function () {
                    if (started) { return; }
                    started = true;
                    track('form_started', { locale: locale });
                }, { once: false });
            }
        })();
    </script>

</body>
</html>
