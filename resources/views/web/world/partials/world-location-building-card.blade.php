<button
    type="button"
    class="vs-location-card vs-location-card--building"
    data-building-link
    data-building-name="{{ $building['name'] }}"
    data-building-desc="{{ $building['description'] ?? '' }}"
    data-building-icon="{{ $building['icon'] ?? '🏠' }}"
>
    <span class="vs-location-card__icon" aria-hidden="true">{{ $building['icon'] ?? '🏠' }}</span>
    <span class="vs-location-card__body">
        <span class="vs-location-card__label">{{ $building['name'] }}</span>
        @if (! empty($building['description']))
            <span class="vs-location-card__desc">{{ $building['description'] }}</span>
        @endif
    </span>
</button>