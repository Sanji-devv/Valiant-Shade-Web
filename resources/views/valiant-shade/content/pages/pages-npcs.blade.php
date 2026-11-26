@extends('valiant-shade.layouts/layoutMaster')

@section('title', __('NPC Archive'))

@php
  use App\ValiantShade\Support\NpcStatusCatalog;
  $statusBadgeMap = collect(NpcStatusCatalog::options())
      ->mapWithKeys(fn (array $meta, int $value) => [$value => 'bg-label-' . ($meta['badge'] ?? 'secondary')])
      ->all();
  $publicRosterUrl = \App\Support\PublicSiteUrls::npcRoster();
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h3 class="mb-1"><i class="icon-base ti tabler-users-group me-2 text-warning"></i>{{ __('NPC Archive') }}</h3>
      <p class="text-body-secondary mb-0">{{ __('Record NPCs with portraits, titles, professions, and backgrounds. Approved entries appear on the public roster.') }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ $publicRosterUrl }}" class="btn btn-label-warning" target="_blank" rel="noopener">{{ __('Public NPC Roster') }}</a>
      <a href="{{ route('valiant-shade.pages-archive') }}" class="btn btn-label-primary">{{ __('Quest Archive') }}</a>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
  @endif

  @if(isset($pendingNpcs) && $pendingNpcs->isNotEmpty())
    <div class="card border border-warning border-opacity-50 mb-4">
      <div class="card-header">
        <h5 class="mb-0 text-warning"><i class="icon-base ti tabler-hourglass me-2"></i>{{ __('Pending NPC Submissions') }} ({{ $pendingNpcs->count() }})</h5>
      </div>
      <div class="list-group list-group-flush">
        @foreach($pendingNpcs as $pending)
          <div class="list-group-item bg-transparent">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
              <div>
                <h6 class="mb-1">
                  {{ $pending->displayName() }}
                  @if($pending->corrects_npc_id && $pending->correctsNpc)
                    <span class="badge bg-label-info ms-1">{{ __('Roster correction') }}</span>
                  @endif
                </h6>
                @if($pending->correctsNpc)
                  <p class="text-info small mb-1">{{ __('Correction proposal for :name', ['name' => $pending->correctsNpc->displayName()]) }}</p>
                @endif
                @if($pending->titles->isNotEmpty())
                  <p class="text-warning small mb-1">{{ $pending->titles->pluck('name')->implode(', ') }}</p>
                @endif
                <p class="text-body-secondary small mb-0">{{ \Illuminate\Support\Str::limit($pending->summary ?: $pending->background, 160) }}</p>
              </div>
              <div class="d-flex gap-2 flex-shrink-0">
                <a href="{{ route('valiant-shade.pages-npcs.show', $pending) }}" class="btn btn-sm btn-label-secondary">{{ __('Review') }}</a>
                <form method="post" action="{{ route('valiant-shade.pages-npcs.approve', $pending) }}">@csrf<button type="submit" class="btn btn-sm btn-success">{{ __('Approve') }}</button></form>
                <form method="post" action="{{ route('valiant-shade.pages-npcs.reject', $pending) }}">@csrf<button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button></form>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  <div class="card mb-4">
    <div class="card-body">
      <form method="get" action="{{ route('valiant-shade.pages-npcs') }}" class="d-flex flex-wrap gap-2" role="search">
        <input id="npc-search" name="search" class="form-control flex-grow-1" style="min-width: 220px; width: auto;"
               value="{{ $search }}" maxlength="255" placeholder="{{ __('Search NPC names, titles, professions, or backgrounds...') }}">
        <select name="status" class="form-select" style="width: auto; min-width: 150px;" aria-label="{{ __('Life status') }}">
          <option value="">{{ __('All life statuses') }}</option>
          @foreach(NpcStatusCatalog::options() as $value => $meta)
            <option value="{{ $value }}" @selected((string) ($statusFilter ?? '') === (string) $value)>{{ __('npc_status.' . $meta['key']) }}</option>
          @endforeach
        </select>
        @if(auth()->user()?->isAdmin())
          <select name="approval" class="form-select" style="width: auto; min-width: 160px;" aria-label="{{ __('Approval filter') }}">
            @foreach(['approved' => __('Approved only'), 'pending' => __('Pending'), 'rejected' => __('Rejected'), 'all' => __('All records')] as $key => $label)
              <option value="{{ $key }}" @selected(($approvalFilter ?? 'approved') === $key)>{{ $label }}</option>
            @endforeach
          </select>
        @endif
        <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
        @if($search !== '' || $statusFilter !== null || ($approvalFilter ?? '') !== 'approved')
          <a class="btn btn-label-secondary" href="{{ route('valiant-shade.pages-npcs') }}">{{ __('Clear Search') }}</a>
        @endif
      </form>
    </div>
  </div>

  <details class="card mb-4" @if($errors->any()) open @endif>
    <summary class="card-header cursor-pointer fw-semibold"><i class="icon-base ti tabler-plus me-2"></i>{{ __('Add NPC') }}</summary>
    <div class="card-body">
      @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
      @endif
      <form method="post" action="{{ route('valiant-shade.pages-npcs.store') }}" enctype="multipart/form-data" data-npc-admin-form>@csrf
        <div class="row g-4 align-items-start">
          <div class="col-lg-7">
            @include('valiant-shade.content.pages.partials.npc-form-fields')
            <button class="btn btn-primary mt-3" type="submit">{{ __('Save NPC') }}</button>
          </div>
          <div class="col-lg-5">
            @include('valiant-shade.content.pages.partials.npc-live-preview')
          </div>
        </div>
      </form>
    </div>
  </details>

  <div class="row g-3">
    @forelse($npcs as $npc)
      <div class="col-md-6 col-xl-4">
        <article class="card h-100">
          <a href="{{ route('valiant-shade.pages-npcs.show', $npc) }}">
            @if($npc->imageUrl())
              <img src="{{ $npc->imageUrl() }}" alt="{{ $npc->displayName() }}" class="card-img-top" style="height: 220px; object-fit: cover;">
            @else
              <div class="d-flex align-items-center justify-content-center bg-label-secondary" style="height: 220px;"><i class="icon-base ti tabler-user" style="font-size: 4rem;"></i></div>
            @endif
          </a>
          <div class="card-body">
            <div class="d-flex justify-content-between gap-2 mb-1">
              <h5 class="mb-0"><a href="{{ route('valiant-shade.pages-npcs.show', $npc) }}">{{ $npc->displayName() }}</a></h5>
              @if($npc->status)
                <span class="badge {{ $statusBadgeMap[$npc->status] ?? 'bg-label-secondary' }}">{{ $npc->statusLabel() }}</span>
              @endif
            </div>
            @if($npc->titles->isNotEmpty())
              <p class="text-warning small fw-medium mb-2">{{ $npc->titles->pluck('name')->implode(', ') }}</p>
            @endif
            @if(!$npc->isApproved())
              <span class="badge bg-label-warning mb-2">{{ __('Pending approval') }}</span>
            @endif
            <p class="text-body-secondary small">{{ \Illuminate\Support\Str::limit($npc->summary ?: $npc->background, 120) }}</p>
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

@section('page-script')
  @vite(['resources/js/valiant-shade/npc-admin-form.js'])
@endsection