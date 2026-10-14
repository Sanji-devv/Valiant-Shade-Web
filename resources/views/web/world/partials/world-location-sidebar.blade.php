@php
    use App\Support\WorldNavigation;

    $useKingdomAccordions = $useKingdomAccordions ?? false;
@endphp

@if ($useKingdomAccordions)
    @include('web.world.partials.world-kingdom-accordions', [
        'currentLocationId' => $currentLocationId,
        'sidebarTitle' => $sidebarTitle ?? null,
    ])
@else
    @php
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
                        @include('web.world.partials.world-location-destinations-list', [
                            'destinations' => $childLinks,
                            'buildings' => $childBuildings,
                        ])
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
