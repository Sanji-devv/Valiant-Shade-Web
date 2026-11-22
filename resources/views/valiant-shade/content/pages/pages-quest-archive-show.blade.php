@extends('valiant-shade.layouts/layoutMaster')

@section('title', $report->title)

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <a href="{{ route('valiant-shade.pages-archive') }}" class="btn btn-label-secondary mb-4">
    &larr; {{ __('Back to archive') }}
  </a>
  @if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
  @endif
  <article class="card">
    <div class="card-header border-bottom">
      <h3 class="mb-1">{{ $report->title }}</h3>
      @if($report->quest_date)
        <span class="text-body-secondary">{{ __('Quest date') }}: {{ $report->quest_date->format('d.m.Y') }}</span>
      @endif
    </div>
    <div class="card-body">
      @foreach([
        __('Players and reporter') => $report->players,
        __('Game masters') => $report->game_masters,
        __('Loot') => $report->loot,
        __('Expenses') => $report->expenses,
        __('Quest outcome') => $report->outcome,
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
</div>
@endsection
