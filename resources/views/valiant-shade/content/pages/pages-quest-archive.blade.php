@extends('valiant-shade.layouts/layoutMaster')

@section('title', __('Quest Archive'))

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h3 class="mb-1"><i class="icon-base ti tabler-books me-2 text-warning"></i>{{ __('Quest Archive') }}</h3>
      <p class="text-body-secondary mb-0">{{ __('Record adventures and search their titles and contents.') }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('valiant-shade.pages-npcs') }}" class="btn btn-label-primary">{{ __('NPC Archive') }}</a>
      <a href="{{ route('web.home') }}" class="btn btn-label-secondary">{{ __('Main page') }}</a>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="get" action="{{ route('valiant-shade.pages-archive') }}" class="d-flex flex-wrap gap-2" role="search">
        <label for="archive-search" class="visually-hidden">{{ __('Search quest reports') }}</label>
        <input id="archive-search" name="search" class="form-control flex-grow-1" style="min-width: 220px; width: auto;"
               value="{{ $search }}" maxlength="255" placeholder="{{ __('Search quest titles or report contents...') }}">
        <button class="btn btn-primary" type="submit">{{ __('Search') }}</button>
        @if($search !== '')
          <a class="btn btn-label-secondary" href="{{ route('valiant-shade.pages-archive') }}">{{ __('Clear Search') }}</a>
        @endif
      </form>
      <small class="text-body-secondary d-block mt-2">{{ __('Title matches appear before content matches.') }}</small>
    </div>
  </div>

  <details class="card mb-4" @if($errors->any()) open @endif>
    <summary class="card-header cursor-pointer fw-semibold"><i class="icon-base ti tabler-plus me-2"></i>{{ __('Add quest report') }}</summary>
    <div class="card-body">
      @if($errors->any())
        <div class="alert alert-danger" role="alert">
          <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif
      <form method="post" action="{{ route('valiant-shade.pages-archive.store') }}">
        @csrf
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label" for="archive-title">{{ __('Quest title') }} *</label>
            <input id="archive-title" class="form-control" name="title" value="{{ old('title') }}" maxlength="255" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="archive-date">{{ __('Quest date') }}</label>
            <input id="archive-date" class="form-control" type="date" name="quest_date" value="{{ old('quest_date') }}">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="archive-players">{{ __('Players and reporter') }}</label>
            <textarea id="archive-players" class="form-control" name="players" rows="4" maxlength="10000">{{ old('players') }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="archive-gms">{{ __('Game masters') }}</label>
            <textarea id="archive-gms" class="form-control" name="game_masters" rows="4" maxlength="2000">{{ old('game_masters') }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="archive-loot">{{ __('Loot') }}</label>
            <textarea id="archive-loot" class="form-control" name="loot" rows="3" maxlength="10000">{{ old('loot') }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="archive-expenses">{{ __('Expenses') }}</label>
            <textarea id="archive-expenses" class="form-control" name="expenses" rows="3" maxlength="10000">{{ old('expenses') }}</textarea>
          </div>
          <div class="col-12">
            <label class="form-label" for="archive-outcome">{{ __('Quest outcome') }}</label>
            <textarea id="archive-outcome" class="form-control" name="outcome" rows="3" maxlength="10000">{{ old('outcome') }}</textarea>
          </div>
          <div class="col-12">
            <label class="form-label" for="archive-report">{{ __('Quest report') }} *</label>
            <textarea id="archive-report" class="form-control" name="report" rows="12" maxlength="100000" required>{{ old('report') }}</textarea>
          </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">{{ __('Save report') }}</button>
      </form>
    </div>
  </details>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">{{ __('Quest reports') }}</h4>
    <span class="text-body-secondary">{{ $reports->total() }}</span>
  </div>
  <div class="row g-3">
    @forelse($reports as $report)
      <div class="col-md-6 col-xl-4">
        <article class="card h-100">
          <div class="card-body d-flex flex-column">
            <h5><a href="{{ route('valiant-shade.pages-archive.show', $report) }}">{{ $report->title }}</a></h5>
            @if($report->quest_date)
              <small class="text-body-secondary mb-2">{{ $report->quest_date->format('d.m.Y') }}</small>
            @endif
            <p class="text-body-secondary mb-3">{{ \Illuminate\Support\Str::limit($report->report, 180) }}</p>
            <a class="mt-auto" href="{{ route('valiant-shade.pages-archive.show', $report) }}">{{ __('Read report') }} &rarr;</a>
          </div>
        </article>
      </div>
    @empty
      <div class="col-12"><div class="alert alert-secondary">{{ __('No quest reports found.') }}</div></div>
    @endforelse
  </div>
  <div class="mt-4">{{ $reports->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
