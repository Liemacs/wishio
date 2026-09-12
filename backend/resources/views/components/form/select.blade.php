@props(['name', 'label', 'options', 'required' => false])

<x-form.field :name="$name" :label="$label" :required="$required">
    <select name="{{ $name }}" id="{{ $name }}" @required($required)
            class="w-full appearance-none rounded-button border border-surface-200 bg-white px-3.5 py-2.5
                   text-base focus:border-primary-400 focus:ring-2 focus:ring-primary-100 focus:outline-none">
        <option value="">—</option>
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected(old($name) === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>
</x-form.field>
