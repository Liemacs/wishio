@props(['source' => null])

@php
    // Butoanele apar doar pentru magazinele în care aplicația e deja publicată.
    $stores = array_keys(array_filter([
        'ios'     => config('wishio.app.store_url.ios'),
        'android' => config('wishio.app.store_url.android'),
    ]));
    $source ??= request('src');
@endphp

@if ($stores)
    <div {{ $attributes->merge(['class' => 'flex flex-col gap-3 sm:flex-row sm:justify-center']) }}>
        @foreach ($stores as $platform)
            <a href="{{ route('app.download', array_filter(['platform' => $platform, 'src' => $source])) }}"
               class="rounded-button bg-surface-900 px-6 py-3.5 text-center text-base font-semibold text-white transition hover:bg-surface-800">
                {{ __("home.stores.$platform") }}
            </a>
        @endforeach
    </div>
@else
    {{ $slot }}
@endif
