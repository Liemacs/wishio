{{-- Raspunsul 429 al formularelor publice (/cerere, /@slug). Se randeaza din
     bootstrap/app.php, cu mesajul deja tradus. --}}
<x-layouts.landing :title="$message">
    <section class="mx-auto max-w-lg pt-20 pb-24 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-surface-100 text-2xl">
            ⏳
        </div>
        <h1 class="mt-6 text-2xl font-bold tracking-tight">
            {{ $message }}
        </h1>
        <a href="{{ url('/') }}" class="mt-8 inline-block rounded-button bg-surface-900 px-6 py-3 text-sm font-semibold text-white">
            Wishio
        </a>
    </section>
</x-layouts.landing>
