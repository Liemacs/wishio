<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? __('landing.meta.title') }}</title>
    <meta name="description" content="{{ __('landing.meta.description') }}">

    {{-- Faza 0: nu vrem inca indexare. Vezi docs/14 § 6. --}}
    <meta name="robots" content="noindex">

    @foreach (config('wishio.locales.supported') as $alt)
        <link rel="alternate" hreflang="{{ $alt }}" href="{{ route('locale.switch', $alt) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ url('/') }}">

    @vite(['resources/css/app.css'])

    @if (config('services.posthog.key'))
        <script>window.__posthogKey = @json(config('services.posthog.key'));</script>
    @endif
</head>
<body class="min-h-screen bg-surface-50 text-surface-900 antialiased">

    <header class="mx-auto flex max-w-3xl items-center justify-between px-5 py-5">
        <a href="{{ route('landing') }}" class="text-lg font-bold tracking-tight">
            Wishio
        </a>

        <nav class="flex gap-1 rounded-full bg-surface-200/70 p-1" aria-label="{{ __('wishio.occasions.birthday') }}">
            @foreach (config('wishio.locales.supported') as $code)
                <a href="{{ route('locale.switch', $code) }}"
                   class="rounded-full px-3 py-1 text-xs font-semibold uppercase transition
                          {{ app()->getLocale() === $code
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

</body>
</html>
