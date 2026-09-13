<x-layouts.landing :title="__('home.meta.title')" :description="__('home.meta.description')" :indexable="true">

    {{-- ── Promisiunea (docs/01 § 1) ─────────────────────────────────────── --}}
    <section class="pt-6 pb-12 text-center sm:pt-12">
        <img src="/icon-512.png" alt="" width="96" height="96" class="mx-auto rounded-[22px] shadow-lg shadow-primary-200/60">

        <p class="mt-6 text-xs font-semibold uppercase tracking-wider text-primary-600">{{ __('home.hero.eyebrow') }}</p>

        <h1 class="mt-3 text-4xl font-bold leading-tight tracking-tight text-balance sm:text-5xl">
            {{ __('home.hero.title') }}
        </h1>

        <p class="mx-auto mt-4 max-w-xl text-lg leading-relaxed text-surface-600">{{ __('home.hero.subtitle') }}</p>

        <x-store-buttons class="mt-8">
            <p class="mt-8 inline-block rounded-full bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700">
                {{ __('home.stores.soon') }}
            </p>
        </x-store-buttons>
    </section>

    {{-- ── Ce face ───────────────────────────────────────────────────────── --}}
    <section class="grid gap-3 sm:grid-cols-3">
        @foreach (__('home.features') as $feature)
            <div class="rounded-card bg-white p-5 shadow-sm ring-1 ring-surface-200/60">
                <h2 class="font-semibold">{{ $feature['title'] }}</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-surface-500">{{ $feature['text'] }}</p>
            </div>
        @endforeach
    </section>

    {{-- ── Datele, spuse înainte de instalare (docs/06) ──────────────────── --}}
    <section class="mt-12 rounded-card bg-white p-6 shadow-sm ring-1 ring-surface-200/60">
        <h2 class="text-lg font-semibold">{{ __('home.privacy.title') }}</h2>

        <ul class="mt-3 space-y-2">
            @foreach (__('home.privacy.items') as $item)
                <li class="flex gap-2.5 text-sm leading-relaxed text-surface-600">
                    <span class="mt-2 h-1 w-1 shrink-0 rounded-full bg-primary-400"></span>
                    <span>{{ $item }}</span>
                </li>
            @endforeach
        </ul>

        <a href="{{ route('legal', ['key' => 'privacy', 'lang' => app()->getLocale()]) }}" class="mt-4 inline-block text-sm text-primary-600">
            {{ __('home.privacy.link') }}
        </a>
    </section>

    {{-- ── Încă o dată, la final ─────────────────────────────────────────── --}}
    <section class="mt-12 text-center">
        <h2 class="text-2xl font-bold tracking-tight text-balance">{{ __('home.cta.title') }}</h2>
        <p class="mt-2 text-surface-600">{{ __('home.cta.subtitle') }}</p>

        <x-store-buttons class="mt-6">
            <p class="mt-6 text-sm text-surface-500">{{ __('home.stores.soon') }}</p>
        </x-store-buttons>
    </section>

</x-layouts.landing>
