@php
    $categoryLabels = $categoryLabels ?? [
        'city' => 'Şehirler',
        'tower' => 'Gözcü kuleleri',
        'wilds' => 'Vahşi alanlar',
    ];
    $groupedChildLinks = collect($destinations ?? [])->groupBy(fn ($item) => $item['category'] ?? '_other');
    $childLinkCategoryOrder = ['city', 'tower', 'wilds', '_other'];
@endphp
<ul class="vs-world-nav__children">
    @foreach ($childLinkCategoryOrder as $category)
        @php
            $items = $groupedChildLinks->get($category, collect());
        @endphp
        @continue($items->isEmpty())
        @if ($category !== '_other' && ! empty($categoryLabels[$category]))
            <li class="vs-world-nav__category">{{ $categoryLabels[$category] }}</li>
        @endif
        @foreach ($items as $destination)
            <li>
                @if (! empty($destination['href']))
                    <a
                        href="{{ $destination['href'] }}"
                        class="vs-image-map-sidebar__link vs-world-nav__child-link"
                        data-location-link
                    >
                        <span class="vs-image-map-sidebar__label">{{ $destination['label'] }}</span>
                        @if (! empty($destination['description']))
                            <span class="vs-image-map-sidebar__desc">{{ $destination['description'] }}</span>
                        @endif
                    </a>
                @else
                    <button
                        type="button"
                        class="vs-image-map-sidebar__link vs-building-menu__item vs-world-nav__building"
                        data-poi-link
                        data-poi-name="{{ $destination['label'] }}"
                        data-poi-desc="{{ $destination['description'] ?? '' }}"
                        data-poi-icon="{{ $destination['icon'] ?? '📍' }}"
                        @include('web.world.partials.hotspot-poi-attributes', ['destination' => $destination])
                    >
                        <span class="vs-building-menu__icon">{{ $destination['icon'] ?? '📍' }}</span>
                        <span class="vs-image-map-sidebar__label">{{ $destination['label'] }}</span>
                        @if (! empty($destination['description']))
                            <span class="vs-image-map-sidebar__desc">{{ $destination['description'] }}</span>
                        @endif
                    </button>
                @endif
            </li>
        @endforeach
    @endforeach

    @foreach ($buildings ?? [] as $building)
        <li>
            <button
                type="button"
                class="vs-image-map-sidebar__link vs-building-menu__item vs-world-nav__building"
                data-building-link
                data-building-name="{{ $building['name'] }}"
                data-building-desc="{{ $building['description'] }}"
                data-building-icon="{{ $building['icon'] }}"
            >
                <span class="vs-building-menu__icon">{{ $building['icon'] }}</span>
                <span class="vs-image-map-sidebar__label">{{ $building['name'] }}</span>
            </button>
        </li>
    @endforeach
</ul>
