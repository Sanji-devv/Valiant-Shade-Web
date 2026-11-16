@php
    $categoryLabels = $categoryLabels ?? [
        'city' => 'Şehirler',
        'tower' => 'Gözcü kuleleri',
        'wilds' => 'Vahşi alanlar',
    ];
    $grouped = collect($destinations ?? [])->groupBy(fn ($item) => $item['category'] ?? '_other');
@endphp

@foreach (['city', 'tower', 'wilds', '_other'] as $category)
    @php
        $items = $grouped->get($category, collect());
    @endphp
    @continue($items->isEmpty())
    @if ($category !== '_other' && ! empty($categoryLabels[$category]))
        <p class="vs-location-board__category">{{ $categoryLabels[$category] }}</p>
    @endif
    <div class="vs-location-card-grid">
        @foreach ($items as $destination)
            @include('web.world.partials.world-location-destination-card', ['destination' => $destination])
        @endforeach
    </div>
@endforeach

@if (! empty($buildings))
    <p class="vs-location-board__category">Yapılar</p>
    <div class="vs-location-card-grid">
        @foreach ($buildings as $building)
            @include('web.world.partials.world-location-building-card', ['building' => $building])
        @endforeach
    </div>
@endif