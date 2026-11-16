@php
    $isLink = ! empty($destination['href']);
    $tag = $isLink ? 'a' : 'button';
@endphp
<{{ $tag }}
    @class([
        'vs-location-card',
        'vs-location-card--link' => $isLink,
        'vs-location-card--poi' => ! $isLink,
    ])
    @if ($isLink)
        href="{{ $destination['href'] }}"
        data-location-link
    @else
        type="button"
        data-poi-link
        data-poi-name="{{ $destination['label'] }}"
        data-poi-desc="{{ $destination['description'] ?? '' }}"
        data-poi-icon="{{ $destination['icon'] ?? '📍' }}"
        @include('web.world.partials.hotspot-poi-attributes', ['destination' => $destination])
    @endif
>
    <span class="vs-location-card__icon" aria-hidden="true">{{ $destination['icon'] ?? '📍' }}</span>
    <span class="vs-location-card__body">
        <span class="vs-location-card__label">{{ $destination['label'] }}</span>
        @if (! empty($destination['description']))
            <span class="vs-location-card__desc">{{ $destination['description'] }}</span>
        @endif
    </span>
    @if (! empty($destination['map_url']))
        <span class="vs-location-card__hint">Haritada aç</span>
    @endif
</{{ $tag }}>