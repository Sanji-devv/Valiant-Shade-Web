@extends('valiant-shade.layouts/layoutMaster')

@section('title', $report->title)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <a href="{{ route('valiant-shade.pages-archive') }}" class="btn btn-label-secondary">
      &larr; {{ __('Back to archive') }}
    </a>
    @if($linkedQuestReport)
      <a href="{{ route('valiant-shade.pages-quest-reports', ['search' => $linkedQuestReport['title']]) }}" class="btn btn-label-info">
        <i class="icon-base ti tabler-report-analytics me-1"></i>{{ __('View in Quest Reports') }} (#{{ $linkedQuestReport['id'] }})
      </a>
    @elseif($report->quest_report_id)
      <a href="{{ route('valiant-shade.pages-quest-reports') }}" class="btn btn-label-info">
        <i class="icon-base ti tabler-report-analytics me-1"></i>{{ __('View in Quest Reports') }} (#{{ $report->quest_report_id }})
      </a>
    @endif
  </div>

  @if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
  @endif

  <article class="card">
    <div class="card-header border-bottom">
      <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
        <h3 class="mb-0">{{ $report->title }}</h3>
        @if($report->quest_report_id)
          <span class="badge bg-label-info">{{ __('Quest Report') }} #{{ $report->quest_report_id }}</span>
        @endif
      </div>
      @if($report->quest_date)
        <span class="text-body-secondary">{{ __('Quest date') }}: {{ $report->quest_date->format('d.m.Y') }}</span>
      @endif
    </div>
    <div class="card-body">
      @foreach([
        __('Game master') => $report->game_master,
        __('Reporter') => $report->reporter,
        __('Players') => $report->players,
        __('KIA (Killed in Action)') => $report->KIA,
        __('MIA (Missing in Action)') => $report->MIA,
        __('Loot') => $report->loot,
        __('Expenses') => $report->expenses,
        __('Quest report') => $report->report,
      ] as $label => $text)
        @if($text)
          <section class="mt-4">
            <h5>{{ $label }}</h5>
            <div style="white-space: pre-wrap; overflow-wrap: anywhere;">{{ $text }}</div>
          </section>
        @endif
      @endforeach
    </div>
  </article>

  <details class="card mt-4" @if($errors->any()) open @endif>
    <summary class="card-header cursor-pointer fw-semibold"><i class="icon-base ti tabler-edit me-2"></i>{{ __('Edit Archive Record') }}</summary>
    <div class="card-body">
      @if($errors->any())
        <div class="alert alert-danger" role="alert">
          <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
      @endif
      <form method="post" action="{{ route('valiant-shade.pages-archive.update', $report) }}">
        @csrf
        @method('PUT')
        @include('valiant-shade.content.pages.partials.quest-archive-form-fields', ['report' => $report])
        <button class="btn btn-primary mt-3" type="submit">{{ __('Update report') }}</button>
      </form>
    </div>
  </details>
</div>
@endsection
