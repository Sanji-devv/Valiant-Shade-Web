@extends('valiant-shade.layouts/layoutMaster')

@section('title', $npc->displayName())

@php
  use App\ValiantShade\Support\NpcStatusCatalog;
  $statusBadgeMap = collect(NpcStatusCatalog::options())
      ->mapWithKeys(fn (array $meta, int $value) => [$value => 'bg-label-' . ($meta['badge'] ?? 'secondary')])
      ->all();
  $canDelete = auth()->user()?->isAdmin() || $npc->user_id === auth()->id();
  $canModerate = auth()->user()?->isAdmin() && $npc->isPending();
@endphp

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <a href="{{ route('valiant-shade.pages-npcs') }}" class="btn btn-label-secondary">&larr; {{ __('Back to NPC Archive') }}</a>
  </div>

  @if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
  @endif

  @if($npc->corrects_npc_id && $npc->correctsNpc)
    <div class="alert alert-info">
      <strong class="d-block mb-1">{{ __('Roster correction') }}</strong>
      <span>{{ __('This submission corrects the published roster entry :name.', ['name' => $npc->correctsNpc->displayName()]) }}</span>
      <a href="{{ route('valiant-shade.pages-npcs.show', $npc->correctsNpc) }}" class="alert-link ms-1">{{ __('Review') }}</a>
    </div>
  @endif

  @if($canModerate)
    <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
      <span>{{ __('This NPC is awaiting approval for the public roster.') }}</span>
      <div class="d-flex gap-2">
        <form method="post" action="{{ route('valiant-shade.pages-npcs.approve', $npc) }}">@csrf<button type="submit" class="btn btn-sm btn-success">{{ __('Approve') }}</button></form>
        <form method="post" action="{{ route('valiant-shade.pages-npcs.reject', $npc) }}">@csrf<button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button></form>
      </div>
    </div>
  @endif

  <article class="card">
    <div class="card-body">
      <div class="row g-4">
        <div class="col-md-4 col-lg-3">
          @if($npc->imageUrl())
            <img src="{{ $npc->imageUrl() }}" alt="{{ $npc->displayName() }}" class="img-fluid rounded border w-100" style="max-height: 360px; object-fit: cover;">
          @else
            <div class="d-flex align-items-center justify-content-center bg-label-secondary rounded border w-100" style="height: 280px;"><i class="icon-base ti tabler-user" style="font-size: 5rem;"></i></div>
          @endif
          <dl class="mt-3 mb-0">
            @if($npc->titles->isNotEmpty())
              <dt class="small text-body-secondary">{{ __('Titles / Epithets') }}</dt>
              <dd class="mb-1 fw-medium text-warning">{{ $npc->titles->pluck('name')->implode(', ') }}</dd>
            @endif
            @foreach([
              __('Profession / Role') => $npc->profession,
              __('Race') => $npc->race,
              __('Faction / Affiliation') => $npc->faction,
              __('Location') => $npc->location,
              __('Status') => $npc->statusLabel(),
            ] as $label => $value)
              @if($value)
                <dt class="small text-body-secondary mt-2">{{ $label }}</dt>
                <dd class="mb-1 fw-medium">{{ $value }}</dd>
              @endif
            @endforeach
          </dl>
        </div>
        <div class="col-md-8 col-lg-9">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <h3 class="mb-0">{{ $npc->displayName() }}</h3>
            @if($npc->status)
              <span class="badge {{ $statusBadgeMap[$npc->status] ?? 'bg-label-secondary' }}">{{ $npc->statusLabel() }}</span>
            @endif
          </div>
          @foreach([
            __('Short summary / Appearance') => $npc->summary,
            __('Background / History') => $npc->background,
            __('Notes & Relationships') => $npc->notes,
          ] as $label => $text)
            @if($text)
              <section class="mt-4">
                <h5>{{ $label }}</h5>
                <div style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $text }}</div>
              </section>
            @endif
          @endforeach
        </div>
      </div>
    </div>
  </article>

  <details class="card mt-4" @if($errors->any()) open @endif>
    <summary class="card-header cursor-pointer fw-semibold"><i class="icon-base ti tabler-edit me-2"></i>{{ __('Edit NPC') }}</summary>
    <div class="card-body">
      @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
      @endif
      <form method="post" action="{{ route('valiant-shade.pages-npcs.update', $npc) }}" enctype="multipart/form-data" data-npc-admin-form>@csrf @method('PUT')
        <div class="row g-4 align-items-start">
          <div class="col-lg-7">
            @include('valiant-shade.content.pages.partials.npc-form-fields', ['npc' => $npc])
            <button class="btn btn-primary mt-3" type="submit">{{ __('Update NPC') }}</button>
          </div>
          <div class="col-lg-5">
            @include('valiant-shade.content.pages.partials.npc-live-preview', ['npc' => $npc])
          </div>
        </div>
      </form>
      @if($canDelete)
        <form method="post" action="{{ route('valiant-shade.pages-npcs.destroy', $npc) }}" class="mt-3" onsubmit="return confirm('{{ __('Are you sure you want to delete this NPC?') }}');">@csrf @method('DELETE')
          <button type="submit" class="btn btn-label-danger"><i class="icon-base ti tabler-trash me-1"></i>{{ __('Delete NPC') }}</button>
        </form>
      @endif
    </div>
  </details>
</div>
@endsection

@section('page-script')
  @vite(['resources/js/valiant-shade/npc-admin-form.js'])
@endsection