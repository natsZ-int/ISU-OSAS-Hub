@props(['field'])

@if ($errors->has($field))
    <span class="field-error" id="{{ $field }}-error">
        {{ $errors->first($field) }}
    </span>
@endif
