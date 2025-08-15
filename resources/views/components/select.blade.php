@props([
    'disabled' => false,
    'options' => [],
    'placeholder' => null,
    'value' => null
])

<select {{ $disabled ? 'disabled' : '' }} 
        value="{{ $value }}"
        {!! $attributes->merge(['class' => 'bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5']) !!}>
    @if($placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif
    @if($slot->isNotEmpty())
        {{ $slot }}
    @else
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" {{ $value == $optionValue ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
    @endif
</select>