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
      <a href="{{ route('valiant-shade.pages-quest-reports') }}" class="btn btn-label-info">{{ __('Quest Reports') }}</a>
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

  <details class="card mb-4" @if($errors->any() || $prefill) open @endif>
    <summary class="card-header cursor-pointer fw-semibold"><i class="icon-base ti tabler-plus me-2"></i>{{ __('Add quest report') }}</summary>
    <div class="card-body">
      @if($errors->any())
        <div class="alert alert-danger" role="alert">
          <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif
      <form method="post" action="{{ route('valiant-shade.pages-archive.store') }}">
        @csrf
        @include('valiant-shade.content.pages.partials.quest-archive-form-fields')
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
            <div class="d-flex justify-content-between align-items-start gap-2">
              <h5><a href="{{ route('valiant-shade.pages-archive.show', $report) }}">{{ $report->title }}</a></h5>
              @if($report->quest_report_id)
                <span class="badge bg-label-info flex-shrink-0">#{{ $report->quest_report_id }}</span>
              @endif
            </div>
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
