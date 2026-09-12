<x-layouts.landing :title="__('landing.thanks.title')">
    <section class="mx-auto max-w-lg pt-20 pb-24 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-surface-100 text-2xl">✉️</div>
        <h1 class="mt-6 text-2xl font-bold tracking-tight">{{ __('wishio.digest.unsubscribe') }}</h1>
        <p class="mx-auto mt-3 max-w-md text-surface-600">{{ __('common.done', ['x' => '']) }}</p>
    </section>
</x-layouts.landing>
