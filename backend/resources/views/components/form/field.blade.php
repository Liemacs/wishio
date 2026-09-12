@props(['name', 'label', 'help' => null, 'required' => false])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-surface-700">
        {{ $label }}
        @unless ($required)
            <span class="font-normal text-surface-400">· {{ __('landing.form.optional') }}</span>
        @endunless
    </label>

    <div class="mt-1.5">{{ $slot }}</div>

    @if ($help)
        <p class="mt-1.5 text-xs leading-relaxed text-surface-400">{{ $help }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 text-xs text-danger">{{ $message }}</p>
    @enderror
</div>
