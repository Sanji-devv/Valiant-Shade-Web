@extends('valiant-shade.layouts/layoutMaster')

@section('title', __('NPC Archive'))

@section('content')
@php
  $statusBadgeMap = [
    'alive' => 'bg-label-success',
    'deceased' => 'bg-label-danger',
    'missing' => 'bg-label-warning',
    'unknown' => 'bg-label-secondary',
  ];
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h3 class="mb-1"><i class="icon-base ti tabler-users-group me-2 text-warning"></i>{{ __('NPC Archive') }}</h3>
      <p class="text-body-secondary mb-0">{{ __('Record NPCs with their portraits, titles, professions, and backgrounds.') }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('valiant-shade.pages-archive') }}" class="btn btn-label-primary">{{ __('Quest Archive') }}</a>
      <a href="{{ route('web.home') }}" class="btn btn-label-secondary">{{ __('Main page') }}</a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
  @endif

  <div class="card mb-4">
    <div class="card-body">
      <form method="get" action="{{ route('valiant-shade.pages-npcs') }}" class="d-flex flex-wrap gap-2" role="search">
        <label for="npc-search" class="visually-hidden">{{ __('Search NPCs') }}</label>
        <input id="npc-search" name="search" class="form-control flex-grow-1" style="min-width: 220px; width: auto;"
               value="{{ $search }}" maxlength="255" placeholder="{{ __('Search NPC names, titles, professions, or backgrounds...') }}">
        <select name="status" class="form-select" style="width: auto; min-width: 150px;" aria-label="{{ __('Status') }}">
          <option value="">{{ __('All statuses') }}</option>
          @foreach(\App\Models\Npc::STATUSES as $statusOption)
            <option value="{{ $statusOption }}" @selected($statusFilter === $statusOption)>
              {{ __('npc_status.' . $statusOption) }}
            </option>
          @endforeach
        </select>
        <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
        @if($search !== '' || $statusFilter !== '')
          <a class="btn btn-label-secondary" href="{{ route('valiant-shade.pages-npcs') }}">{{ __('Clear Search') }}</a>
        @endif
      </form>
      <small class="text-body-secondary d-block mt-2">{{ __('Name and title matches appear before other content matches.') }}</small>
    </div>
  </div>

  <details class="card mb-4" @if($errors->any()) open @endif>
    <summary class="card-header cursor-pointer fw-semibold"><i class="icon-base ti tabler-plus me-2"></i>{{ __('Add NPC') }}</summary>
    <div class="card-body">
      @if($errors->any())
        <div class="alert alert-danger" role="alert">
          <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif
      <form method="post" action="{{ route('valiant-shade.pages-npcs.store') }}" enctype="multipart/form-data">
        @csrf
        @include('valiant-shade.content.pages.partials.npc-form-fields')
        <button class="btn btn-primary mt-3" type="submit">{{ __('Save NPC') }}</button>
      </form>
    </div>
  </details>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">{{ __('NPCs') }}</h4>
    <span class="text-body-secondary">{{ $npcs->total() }}</span>
  </div>

  <div class="row g-3">
    @forelse($npcs as $npc)
      <div class="col-md-6 col-xl-4">
        <article class="card h-100 overflow-hidden">
          <a href="{{ route('valiant-shade.pages-npcs.show', $npc) }}" class="text-decoration-none">
            @if($npc->imageUrl())
              <img src="{{ $npc->imageUrl() }}" alt="{{ $npc->name }}" class="card-img-top" style="height: 240px; object-fit: cover;">
            @else
              <div class="d-flex align-items-center justify-content-center bg-label-secondary" style="height: 240px;">
                <i class="icon-base ti tabler-user" style="font-size: 4rem;"></i>
              </div>
            @endif
          </a>
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
              <h5 class="mb-0">
                <a href="{{ route('valiant-shade.pages-npcs.show', $npc) }}">{{ $npc->name }}</a>
              </h5>
              @if($npc->status)
                <span class="badge {{ $statusBadgeMap[$npc->status] ?? 'bg-label-secondary' }}">
                  {{ __('npc_status.' . $npc->status) }}
                </span>
              @endif
            </div>
            @if($npc->title)
              <div class="text-warning small fw-medium mb-2">{{ $npc->title }}</div>
            @endif
            @if($npc->profession || $npc->race || $npc->location)
              <div class="d-flex flex-wrap gap-1 mb-2">
                @if($npc->profession)
                  <span class="badge bg-label-primary">{{ $npc->profession }}</span>
                @endif
                @if($npc->race)
                  <span class="badge bg-label-info">{{ $npc->race }}</span>
                @endif
                @if($npc->location)
                  <span class="badge bg-label-secondary">{{ $npc->location }}</span>
                @endif
              </div>
            @endif
            @php
              $excerpt = $npc->summary ?: $npc->background;
            @endphp
            @if($excerpt)
              <p class="text-body-secondary mb-3">{{ \Illuminate\Support\Str::limit($excerpt, 150) }}</p>
            @endif
            <a class="mt-auto" href="{{ route('valiant-shade.pages-npcs.show', $npc) }}">{{ __('View NPC') }} &rarr;</a>
          </div>
        </article>
      </div>
    @empty
      <div class="col-12"><div class="alert alert-secondary">{{ __('No NPCs found.') }}</div></div>
    @endforelse
  </div>

  <div class="mt-4">{{ $npcs->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
