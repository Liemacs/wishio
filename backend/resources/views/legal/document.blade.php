@php
    $doc = __("legal.$key");
    // Documentul deschis din aplicatie ramane in limba aplicatiei si dupa un click.
    $keep = request()->only('lang');
@endphp

<x-layouts.landing :title="$doc['title']">

    <article class="mx-auto max-w-2xl pt-6 pb-20">
        <h1 class="text-3xl font-bold tracking-tight">{{ $doc['title'] }}</h1>
        <p class="mt-1 text-xs text-surface-400">{{ __('legal.updated') }}</p>

        <p class="mt-5 text-base leading-relaxed text-surface-700">{{ $doc['intro'] }}</p>

        @foreach ($doc['sections'] as $section)
            <section class="mt-8">
                <h2 class="text-lg font-semibold text-surface-900">{{ $section['title'] }}</h2>

                <ul class="mt-3 space-y-2">
                    @foreach ($section['items'] as $item)
                        <li class="flex gap-2.5 text-sm leading-relaxed text-surface-600">
                            <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-primary-400"></span>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        <div class="mt-12 rounded-card bg-white p-5 text-sm ring-1 ring-surface-200/60">
            <p class="font-semibold text-surface-800">{{ __('legal.operator') }}</p>
            <p class="mt-1 text-surface-500">
                {{ config('wishio.legal.operator_name') ?: 'Wishio' }}<br>
                @if (config('wishio.legal.operator_address'))
                    {{ config('wishio.legal.operator_address') }}<br>
                @endif
                {{ config('wishio.legal.contact_email') ?: 'privacy@wishio.md' }}
            </p>
        </div>

        <nav class="mt-8 flex flex-wrap gap-x-4 gap-y-2 text-sm">
            @foreach (['privacy', 'terms', 'support', 'delete-account'] as $document)
                <a href="{{ route('legal', ['key' => $document] + $keep) }}" class="text-primary-600">{{ __("legal.$document.title") }}</a>
            @endforeach
        </nav>
    </article>

</x-layouts.landing>
