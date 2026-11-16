@foreach ($hotspots as $hotspot)
    @php
        $hasLink = ! empty($hotspot['href']);
        $secret = ! empty($hotspot['secret']);
        $class = 'vs-image-hotspot'.($secret ? ' vs-image-hotspot--secret' : '').(! $hasLink ? ' vs-image-hotspot--poi' : '');
        $style = 'left: '.($hotspot['x'] ?? 0).'%; top: '.($hotspot['y'] ?? 0).'%; width: '.($hotspot['w'] ?? 5).'%; height: '.($hotspot['h'] ?? 5).'%;';
        $icon = $hotspot['icon'] ?? '📍';
    @endphp
    @if ($hasLink)
        <a
            href="{{ $hotspot['href'] }}"
            class="{{ $class }}"
            style="{{ $style }}"
            title="{{ $hotspot['label'] }}"
            aria-label="{{ $hotspot['label'] }}"
            data-hotspot-id="{{ $hotspot['id'] }}"
            data-location-link
        ></a>
    @else
        <button
            type="button"
            class="{{ $class }}"
            style="{{ $style }}"
            title="{{ $hotspot['label'] }}"
            aria-label="{{ $hotspot['label'] }}"
            data-hotspot-id="{{ $hotspot['id'] }}"
            data-poi-link
            data-poi-name="{{ $hotspot['label'] }}"
            data-poi-desc="{{ $hotspot['description'] ?? '' }}"
            data-poi-icon="{{ $icon }}"
            data-poi-id="{{ $hotspot['id'] ?? '' }}"
            data-poi-x="{{ $hotspot['x'] ?? 0 }}"
            data-poi-y="{{ $hotspot['y'] ?? 0 }}"
            data-poi-w="{{ $hotspot['w'] ?? 5 }}"
            data-poi-h="{{ $hotspot['h'] ?? 5 }}"
        ></button>
    @endif
@endforeach
