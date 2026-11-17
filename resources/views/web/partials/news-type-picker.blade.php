@php
    $types = config('news.types', []);
    $noDefault = $noDefault ?? false;
    $defaultType = $noDefault ? '' : array_key_first($types);
    $variant = $variant ?? 'public';
    $groupId = $groupId ?? 'newsTypePicker';
    $inputId = $inputId ?? 'rumorNewsType';
    $inputName = $inputName ?? 'news_type';
    $ariaLabel = $ariaLabel ?? 'Tür';
@endphp

<div
    class="vs-type-picker{{ $variant === 'admin' ? ' vs-type-picker--admin' : '' }}"
    id="{{ $groupId }}"
    role="radiogroup"
    aria-label="{{ $ariaLabel }}"
>
    @foreach($types as $typeKey => $typeConfig)
        <button
            type="button"
            class="vs-type-picker__btn{{ ! $noDefault && $loop->first ? ' is-selected' : '' }}"
            data-type="{{ $typeKey }}"
            role="radio"
            aria-checked="{{ ! $noDefault && $loop->first ? 'true' : 'false' }}"
            aria-label="{{ $typeConfig['label'] }}"
            title="{{ $typeConfig['label'] }}"
        >
            <span class="vs-type-picker__icon" aria-hidden="true">
                @if($variant === 'admin')
                    <i class="{{ $typeConfig['category_icon'] }}"></i>
                @else
                    @include('web.partials.news-type-picker-icon', ['typeKey' => $typeKey])
                @endif
            </span>
            <span class="vs-type-picker__label">{{ $typeConfig['picker_label'] ?? $typeConfig['label'] }}</span>
        </button>
    @endforeach
</div>
<input type="hidden" name="{{ $inputName }}" id="{{ $inputId }}" value="{{ $defaultType }}">
