@extends('valiant-shade.layouts/layoutMaster')

@section('title', $npc->name)

@section('content')
@php
  $statusBadgeMap = [
    'alive' => 'bg-label-success',
    'deceased' => 'bg-label-danger',
    'missing' => 'bg-label-warning',
    'unknown' => 'bg-label-secondary',
  ];
  $canDelete = auth()->user()?->isAdmin() || $npc->user_id === auth()->id();
@endphp
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <a href="{{ route('valiant-shade.pages-npcs') }}" class="btn btn-label-secondary">
      &larr; {{ __('Back to NPC Archive') }}
    </a>
    @if($canDelete)
      <form method="post" action="{{ route('valiant-shade.pages-npcs.destroy', $npc) }}"
            onsubmit="return confirm('{{ __('Are you sure you want to delete this NPC?') }}');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-label-danger">
          <i class="icon-base ti tabler-trash me-1"></i>{{ __('Delete NPC') }}
        </button>
      </form>
    @endif
  </div>

  @if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
  @endif

  <article class="card">
    <div class="card-body">
      <div class="row g-4">
        <div class="col-md-4 col-lg-3">
          @if($npc->imageUrl())
            <img src="{{ $npc->imageUrl() }}" alt="{{ $npc->name }}" class="img-fluid rounded border w-100" style="max-height: 360px; object-fit: cover;">
          @else
            <div class="d-flex align-items-center justify-content-center bg-label-secondary rounded border w-100" style="height: 280px;">
              <i class="icon-base ti tabler-user" style="font-size: 5rem;"></i>
            </div>
          @endif

          <dl class="mt-3 mb-0">
            @foreach([
              __('Title / Epithet') => $npc->title,
              __('Profession / Role') => $npc->profession,
              __('Race') => $npc->race,
              __('Faction / Affiliation') => $npc->faction,
              __('Location') => $npc->location,
              __('Status') => $npc->status ? __('npc_status.' . $npc->status) : null,
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
            <h3 class="mb-0">{{ $npc->name }}</h3>
            @if($npc->status)
              <span class="badge {{ $statusBadgeMap[$npc->status] ?? 'bg-label-secondary' }}">
                {{ __('npc_status.' . $npc->status) }}
              </span>
            @endif
          </div>
          @if($npc->title)
            <p class="text-warning fs-5 mb-3">{{ $npc->title }}</p>
          @endif

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

          @if(!$npc->summary && !$npc->background && !$npc->notes)
            <p class="text-body-secondary mt-3 mb-0">{{ __('No background or notes recorded yet.') }}</p>
          @endif
        </div>
      </div>
    </div>
  </article>

  <details class="card mt-4" @if($errors->any()) open @endif>
    <summary class="card-header cursor-pointer fw-semibold"><i class="icon-base ti tabler-edit me-2"></i>{{ __('Edit NPC') }}</summary>
    <div class="card-body">
      @if($errors->any())
        <div class="alert alert-danger" role="alert">
          <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif
      <form method="post" action="{{ route('valiant-shade.pages-npcs.update', $npc) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('valiant-shade.content.pages.partials.npc-form-fields', ['npc' => $npc])
        <button class="btn btn-primary mt-3" type="submit">{{ __('Update NPC') }}</button>
      </form>
    </div>
  </details>
</div>
@endsection
