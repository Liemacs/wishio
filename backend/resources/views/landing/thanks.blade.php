<x-layouts.landing :title="__('landing.thanks.title')">

    <section class="mx-auto max-w-lg pt-16 pb-24 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 text-2xl">
            🎁
        </div>

        <h1 class="mt-6 text-3xl font-bold tracking-tight">{{ __('landing.thanks.title') }}</h1>

        <p class="mx-auto mt-3 max-w-md leading-relaxed text-surface-600">
            {{ __('landing.thanks.subtitle') }}
        </p>

        <a href="{{ route('landing') }}"
           class="mt-8 inline-block rounded-button bg-surface-900 px-6 py-3 text-sm font-semibold text-white
                  transition hover:bg-surface-800">
            {{ __('landing.thanks.back') }}
        </a>
    </section>

</x-layouts.landing>
