@php
    use App\Support\WorldNavigation;

    $sidebar = WorldNavigation::unifiedSidebar(
        $currentLocationId,
        $destinations ?? [],
        $buildings ?? [],
    );
@endphp

<div class="vs-location-board">
    <h2 class="vs-image-map-sidebar__title">{{ $sidebarTitle ?? 'Bölgeler' }}</h2>

    @if (count($sidebar['trail']) > 1)
        <nav class="vs-location-trail" aria-label="Konum yolu">
            @foreach ($sidebar['trail'] as $crumb)
                @if (! $crumb['is_current'])
                    <a href="{{ $crumb['url'] }}" class="vs-location-trail__chip" data-location-link>{{ $crumb['label'] }}</a>
                    <span class="vs-location-trail__sep" aria-hidden="true">/</span>
                @else
                    <span class="vs-location-trail__chip vs-location-trail__chip--current">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>
    @endif

    <section class="vs-location-board__block vs-location-board__block--current" aria-label="Bu harita">
        <header class="vs-location-board__header">
            <div class="vs-location-board__kingdom vs-location-board__kingdom--current">
                <span class="vs-location-board__kingdom-label">{{ $sidebar['current']['label'] }}</span>
                @if (! empty($sidebar['current']['description']))
                    <span class="vs-location-board__kingdom-desc">{{ $sidebar['current']['description'] }}</span>
                @endif
            </div>
        </header>

        @if (! empty($sidebar['child_groups']))
            <div class="vs-location-board__subsection">
                <p class="vs-location-board__category">Alt haritalar</p>
                @foreach ($sidebar['child_groups'] as $group)
                    <div class="vs-location-board__child-group">
                        <a href="{{ $group['url'] }}" class="vs-location-board__child-map" data-location-link>
                            <span class="vs-location-board__child-label">{{ $group['label'] }}</span>
                            @if (! empty($group['description']))
                                <span class="vs-location-board__child-desc">{{ $group['description'] }}</span>
                            @endif
                        </a>
                        @if (! empty($group['places']))
                            <div class="vs-location-board__place-row">
                                @foreach ($group['places'] as $place)
                                    <a href="{{ $place['url'] }}" class="vs-location-board__place-chip" data-location-link>
                                        {{ $place['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if (! empty($sidebar['map_markers']) || ! empty($sidebar['buildings']))
            <div class="vs-location-board__subsection">
                <p class="vs-location-board__category">Bu haritadaki mekanlar</p>
                @include('web.world.partials.world-location-destination-grid', [
                    'destinations' => $sidebar['map_markers'],
                    'buildings' => $sidebar['buildings'],
                ])
            </div>
        @endif
    </section>

    @if (! empty($sidebar['other_maps']))
        <section class="vs-location-board__block" aria-label="Diğer haritalar">
            <p class="vs-location-board__category">Diğer haritalar</p>
            <div class="vs-location-card-grid vs-location-card-grid--nearby">
                @foreach ($sidebar['other_maps'] as $map)
                    <a
                        href="{{ $map['url'] }}"
                        class="vs-location-card vs-location-card--nearby"
                        data-location-link
                        title="{{ $map['label'] }}"
                    >
                        <span class="vs-location-card__body">
                            <span class="vs-location-card__label">{{ $map['label'] }}</span>
                            @if (! empty($map['description']))
                                <span class="vs-location-card__desc">{{ $map['description'] }}</span>
                            @endif
                        </span>
                        @include('web.world.partials.world-location-card-arrow')
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</div>