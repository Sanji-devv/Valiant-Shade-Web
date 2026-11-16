@php
    $poiCoords = isset($destination['x'], $destination['y']);
@endphp
@if (! empty($destination['id']))
    data-poi-id="{{ $destination['id'] }}"
@endif
@if ($poiCoords)
    data-poi-x="{{ $destination['x'] }}"
    data-poi-y="{{ $destination['y'] }}"
    data-poi-w="{{ $destination['w'] ?? 5 }}"
    data-poi-h="{{ $destination['h'] ?? 5 }}"
@endif
@if (! empty($destination['map_url']))
    data-poi-map-url="{{ $destination['map_url'] }}"
@endif