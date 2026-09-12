<x-layouts.landing :title="__('profile.meta.title', ['name' => $profile->display_name])">

    <section class="pt-4 pb-8 text-center sm:pt-10">
        <p class="text-xs font-semibold uppercase tracking-wider text-primary-600">
            {{ __('profile.hero.invited', ['name' => $profile->display_name]) }}
        </p>

        <h1 class="mt-3 text-3xl font-bold leading-tight tracking-tight sm:text-4xl">
            {{ __('profile.hero.title', ['name' => $profile->display_name]) }}
        </h1>

        <p class="mx-auto mt-3 max-w-md leading-relaxed text-surface-600">
            {{ __('profile.hero.subtitle', ['name' => $profile->display_name]) }}
        </p>
    </section>

    {{-- Ce arată despre el se afișează DOAR dacă a ales explicit. Implicit,
         aproape nimic: pagina e o invitație, nu o vitrină (docs/06 § 5). --}}
    @if ($profile->shows('birth_date') || $profile->shows('interests') || $profile->shows('wishlist'))
        <section class="mb-8 rounded-card bg-white p-5 shadow-sm ring-1 ring-surface-200/60">
            <h2 class="text-sm font-semibold text-surface-500">
                {{ __('profile.about.title', ['name' => $profile->display_name]) }}
            </h2>

            @if ($profile->shows('birth_date') && $profile->user->birth_date)
                <p class="mt-3 text-sm">
                    <span class="text-surface-400">{{ __('profile.about.birthday') }}:</span>
                    {{ $profile->user->birth_date->translatedFormat('j F') }}
                </p>
            @endif

            @if ($profile->shows('wishlist') && $profile->user->wishlistItems->isNotEmpty())
                <p class="mt-3 text-xs font-medium text-surface-400">{{ __('profile.about.wishlist') }}</p>
                <div class="mt-1.5 flex flex-wrap gap-1.5">
                    @foreach ($profile->user->wishlistItems->where('visibility', 'public') as $item)
                        <span class="rounded-full bg-primary-50 px-3 py-1 text-sm text-primary-800">{{ $item->title }}</span>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <section class="overflow-hidden rounded-card bg-white shadow-sm ring-1 ring-surface-200/60">
        <div class="bg-linear-to-br from-primary-50 to-white px-6 py-5">
            <h2 class="text-xl font-semibold">{{ __('profile.form.title') }}</h2>
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

        <form method="POST" action="{{ route('profile.store', $profile->slug) }}"
              data-track-form class="space-y-5 px-6 py-6">
            @csrf

            <x-form.field name="display_name" :label="__('profile.form.name')" required>
                <input type="text" name="display_name" id="display_name" value="{{ old('display_name') }}" required
                       class="w-full rounded-button border border-surface-200 px-3.5 py-2.5 text-base
                              focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none">
            </x-form.field>

            <x-form.field name="birthday" :label="__('profile.form.birthday')" :help="__('profile.form.birthdayHelp')">
                <input type="text" name="birthday" id="birthday" value="{{ old('birthday') }}"
                       inputmode="numeric" placeholder="23.04 · 23.04.1998"
                       class="w-full rounded-button border border-surface-200 px-3.5 py-2.5 text-base
                              placeholder:text-surface-300 focus:border-primary-400 focus:ring-2
                              focus:ring-primary-100 focus:outline-none">
            </x-form.field>

            <x-form.field name="interests" :label="__('profile.form.interests')" :help="__('profile.form.interestsHelp')">
                <div class="mt-1 max-h-64 space-y-4 overflow-y-auto rounded-button bg-surface-50 p-4">
                    @foreach ($interests as $group)
                        <div>
                            <p class="mb-1.5 text-xs font-semibold text-surface-400">
                                {{ $group->icon }} {{ $group->label() }}
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($group->interests as $interest)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" name="interests[]" value="{{ $interest->code }}"
                                               class="peer sr-only"
                                               @checked(in_array($interest->code, old('interests', [])))>
                                        <span class="block rounded-full bg-white px-3 py-1.5 text-sm text-surface-700
                                                     ring-1 ring-surface-200 transition
                                                     peer-checked:bg-primary-600 peer-checked:text-white
                                                     peer-checked:ring-primary-600">
                                            {{ $interest->label() }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-form.field>

            <x-form.field name="message" :label="__('profile.form.message')">
                <input type="text" name="message" id="message" value="{{ old('message') }}" maxlength="280"
                       class="w-full rounded-button border border-surface-200 px-3.5 py-2.5 text-base
                              focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none">
            </x-form.field>

            {{-- Consimțământ explicit și versionat — docs/06 § 5. --}}
            <label class="flex cursor-pointer items-start gap-3 rounded-button bg-surface-50 p-4">
                <input type="checkbox" name="consent" value="1" required
                       class="mt-0.5 h-4 w-4 shrink-0 rounded border-surface-300 text-primary-600 focus:ring-primary-300">
                <span class="text-xs leading-relaxed text-surface-500">
                    {{ __('profile.form.consent', ['name' => $profile->display_name]) }}
                </span>
            </label>

            <button type="submit"
                    class="w-full rounded-button bg-primary-600 px-6 py-3.5 text-base font-semibold text-white
                           transition hover:bg-primary-700 active:scale-[0.99]">
                {{ __('profile.form.submit') }}
            </button>
        </form>
    </section>

</x-layouts.landing>
