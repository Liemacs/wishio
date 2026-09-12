<x-layouts.landing>

    {{-- ── Hero ─────────────────────────────────────────────────────────── --}}
    <section class="pt-6 pb-12 text-center sm:pt-12">
        <p class="text-xs font-semibold uppercase tracking-wider text-primary-600">
            {{ __('landing.hero.eyebrow') }}
        </p>

        <h1 class="mt-4 text-4xl font-bold leading-tight tracking-tight sm:text-5xl">
            {{ __('landing.hero.title') }}
        </h1>

        <p class="mx-auto mt-4 max-w-xl text-lg leading-relaxed text-surface-600">
            {!! preg_replace('/\*\*(.+?)\*\*/u', '<strong class="text-surface-900">$1</strong>', e(__('landing.hero.subtitle'))) !!}
        </p>
    </section>

    {{-- ── Cum functioneaza ─────────────────────────────────────────────── --}}
    <section class="mb-12 grid gap-3 sm:grid-cols-3">
        @foreach (__('landing.how.steps') as $index => $step)
            <div class="rounded-card bg-white p-5 shadow-sm ring-1 ring-surface-200/60">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-primary-50 text-sm font-bold text-primary-600">
                    {{ $index + 1 }}
                </span>
                <h3 class="mt-3 font-semibold">{{ $step['title'] }}</h3>
                <p class="mt-1 text-sm leading-relaxed text-surface-500">{{ $step['text'] }}</p>
            </div>
        @endforeach
    </section>

    {{-- ── Formular ─────────────────────────────────────────────────────── --}}
    <section id="form" class="overflow-hidden rounded-card bg-white shadow-sm ring-1 ring-surface-200/60">
        <div class="bg-linear-to-br from-primary-50 to-white px-6 py-5">
            <h2 class="text-xl font-semibold">{{ __('landing.form.title') }}</h2>
        </div>

        @if ($errors->any())
            <div class="mx-6 mt-5 rounded-button bg-primary-50 px-4 py-3 text-sm text-primary-800">
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('landing.request') }}" class="space-y-5 px-6 py-6">
            @csrf
            <input type="hidden" name="src" value="{{ request('src') }}">

            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.select name="relationship" :label="__('landing.form.relationship')"
                               :options="__('landing.relationships')" required />
                <x-form.select name="age_bracket" :label="__('landing.form.age')"
                               :options="__('landing.ages')" />
            </div>

            {{-- Cel mai important camp din toata Faza 0: devine corpusul pentru S7. --}}
            <x-form.field name="about" :label="__('landing.form.about')" :help="__('landing.form.about_help')" required>
                <textarea name="about" id="about" rows="4" required
                          placeholder="{{ __('landing.form.about_placeholder') }}"
                          class="w-full rounded-button border border-surface-200 px-3.5 py-2.5 text-base
                                 placeholder:text-surface-300 focus:border-primary-400 focus:ring-2
                                 focus:ring-primary-100 focus:outline-none"
                >{{ old('about') }}</textarea>
            </x-form.field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.select name="budget" :label="__('landing.form.budget')"
                               :options="__('landing.budgets')" required />
                <x-form.select name="recipient_gender" :label="__('landing.form.gender')"
                               :options="__('landing.genders')" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.select name="occasion" :label="__('landing.form.occasion')"
                               :options="__('landing.occasions')" />
                <x-form.field name="occasion_date" :label="__('landing.form.occasion_date')">
                    <input type="date" name="occasion_date" id="occasion_date" value="{{ old('occasion_date') }}"
                           min="{{ now()->toDateString() }}"
                           class="w-full rounded-button border border-surface-200 px-3.5 py-2.5 text-base
                                  focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none">
                </x-form.field>
            </div>

            <x-form.field name="contact" :label="__('landing.form.contact')" :help="__('landing.form.contact_help')" required>
                <input type="text" name="contact" id="contact" value="{{ old('contact') }}" required
                       placeholder="{{ __('landing.form.contact_placeholder') }}"
                       class="w-full rounded-button border border-surface-200 px-3.5 py-2.5 text-base
                              placeholder:text-surface-300 focus:border-primary-400 focus:ring-2
                              focus:ring-primary-100 focus:outline-none">
            </x-form.field>

            {{-- Consimtamant explicit, versionat. Vezi docs/06-privacy-legal.md. --}}
            <label class="flex cursor-pointer items-start gap-3 rounded-button bg-surface-50 p-4">
                <input type="checkbox" name="consent" value="1" required
                       class="mt-0.5 h-4 w-4 shrink-0 rounded border-surface-300 text-primary-600
                              focus:ring-primary-300">
                <span class="text-xs leading-relaxed text-surface-500">{{ __('landing.form.consent') }}</span>
            </label>

            <button type="submit"
                    class="w-full rounded-button bg-primary-600 px-6 py-3.5 text-base font-semibold text-white
                           transition hover:bg-primary-700 active:scale-[0.99]">
                {{ __('landing.form.submit') }}
            </button>
        </form>
    </section>

</x-layouts.landing>
