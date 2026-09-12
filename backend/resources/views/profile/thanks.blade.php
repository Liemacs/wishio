<x-layouts.landing :title="__('profile.thanks.title', ['name' => $profile->display_name])" event="profile_submission">

    <section class="mx-auto max-w-lg pt-14 pb-20 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 text-2xl">🎂</div>

        <h1 class="mt-6 text-2xl font-bold leading-tight tracking-tight">
            {{ __('profile.thanks.title', ['name' => $profile->display_name]) }}
        </h1>

        <p class="mt-3 leading-relaxed text-surface-600">{{ __('profile.thanks.subtitle') }}</p>

        {{-- Bucla virală se închide AICI: vizitatorul tocmai a văzut la ce
             folosește produsul, pe propriile lui date (docs/01 § Reframe 2). --}}
        <div class="mt-10 rounded-card bg-white p-6 shadow-sm ring-1 ring-surface-200/60">
            <p class="text-base font-medium">{{ __('profile.thanks.own') }}</p>
            <a href="{{ url('/') }}"
               class="mt-4 block rounded-button bg-primary-600 px-6 py-3 text-base font-semibold text-white">
                {{ __('profile.thanks.install') }}
            </a>
        </div>

        <a href="{{ route('profile.destroy', ['slug' => $profile->slug, 'token' => $token]) }}"
           class="mt-8 inline-block text-xs text-surface-400 underline">
            {{ __('profile.thanks.delete') }}
        </a>
    </section>

</x-layouts.landing>
