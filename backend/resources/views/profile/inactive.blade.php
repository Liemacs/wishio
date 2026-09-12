<x-layouts.landing :title="__('profile.inactive.title', ['name' => ''])">
    <section class="mx-auto max-w-lg pt-20 pb-24 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-surface-100 text-2xl">
            {{ 'inactive' === 'deleted' ? '🗑️' : '🔗' }}
        </div>
        <h1 class="mt-6 text-2xl font-bold tracking-tight">
            {{ __('profile.inactive.title', ['name' => '']) }}
        </h1>
        <p class="mt-3 text-surface-600">
            {{ __('profile.inactive.subtitle', ['name' => '']) }}
        </p>
        <a href="{{ url('/') }}" class="mt-8 inline-block rounded-button bg-surface-900 px-6 py-3 text-sm font-semibold text-white">
            Wishio
        </a>
    </section>
</x-layouts.landing>
