@php
    use App\Support\WorldNavigation;

    $locationChain = WorldNavigation::chain($currentLocationId);
    $depth = count($locationChain);
    $childLinks = $destinations ?? [];
    $childBuildings = $buildings ?? [];
@endphp

<div class="vs-world-nav">
    <h2 class="vs-image-map-sidebar__title">{{ $sidebarTitle ?? 'Bölgeler' }}</h2>
    <ul class="vs-image-map-sidebar__list vs-world-nav__list">
        @foreach ($locationChain as $index => $location)
            @php
                $isCurrent = $index === $depth - 1;
                $level = $index;
            @endphp
            <li class="vs-world-nav__item" style="--vs-nav-level: {{ $level }}">
                @if ($isCurrent)
                    <div class="vs-image-map-sidebar__link vs-world-nav__link vs-world-nav__link--current" aria-current="location">
                        <span class="vs-image-map-sidebar__label">{{ $location['label'] }}</span>
                        @if (! empty($location['description']))
                            <span class="vs-image-map-sidebar__desc">{{ $location['description'] }}</span>
                        @endif
                    </div>
                @else
                    <a
                        href="{{ route($location['route']) }}"
                        class="vs-image-map-sidebar__link vs-world-nav__link"
                    >
                        <span class="vs-image-map-sidebar__label">{{ $location['label'] }}</span>
                        @if (! empty($location['description']))
                            <span class="vs-image-map-sidebar__desc">{{ $location['description'] }}</span>
                        @endif
                    </a>
                @endif

                @if ($isCurrent && (! empty($childLinks) || ! empty($childBuildings)))
                    <ul class="vs-world-nav__children">
                        @foreach ($childLinks as $destination)
                            <li>
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
                            </li>
                        @endforeach

                        @foreach ($childBuildings as $building)
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
                @endif
            </li>
        @endforeach
    </ul>
</div>
