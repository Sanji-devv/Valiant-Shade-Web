@include('web.world.partials.world-map-location-board', [
    'currentLocationId' => $currentLocationId,
    'sidebarTitle' => $sidebarTitle ?? null,
    'destinations' => $destinations ?? [],
    'buildings' => $buildings ?? [],
])