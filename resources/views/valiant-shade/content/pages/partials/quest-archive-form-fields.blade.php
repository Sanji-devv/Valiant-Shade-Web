@php
  $editing = isset($report) && $report !== null;
  $prefill = $prefill ?? [];
  $selectedQuestReportId = (string) old(
    'quest_report_id',
    $editing ? ($report->quest_report_id ?? '') : ($prefill['quest_report_id'] ?? '')
  );
@endphp

<div class="row g-3">
  <div class="col-12">
    <label class="form-label" for="archive-quest-report">{{ __('Linked Quest Report') }}</label>
    <select id="archive-quest-report" class="form-select" name="quest_report_id">
      <option value="">{{ __('Not linked to a Quest Report') }}</option>
      @foreach($questReports as $qr)
        <option value="{{ $qr['id'] }}" @selected($selectedQuestReportId === (string) $qr['id'])>
          #{{ $qr['id'] }} — {{ $qr['title'] }}@if($qr['date'] !== '') ({{ $qr['date'] }})@endif
        </option>
      @endforeach
    </select>
    <small class="text-body-secondary d-block mt-1">{{ __('Linking lets players jump directly from Quest Reports to this archive record.') }}</small>
  </div>

  <div class="col-md-8">
    <label class="form-label" for="archive-title">{{ __('Quest title') }} *</label>
    <input id="archive-title" class="form-control" name="title"
           value="{{ old('title', $editing ? $report->title : ($prefill['title'] ?? '')) }}" maxlength="255" required>
  </div>
  <div class="col-md-4">
    <label class="form-label" for="archive-date">{{ __('Quest date') }}</label>
    <input id="archive-date" class="form-control" type="date" name="quest_date"
           value="{{ old('quest_date', $editing ? optional($report->quest_date)->format('Y-m-d') : ($prefill['quest_date'] ?? '')) }}">
  </div>

  <div class="col-md-6">
    <label class="form-label" for="archive-gm">{{ __('Game master') }}</label>
    <textarea id="archive-gm" class="form-control" name="game_master" rows="2" maxlength="2000">{{ old('game_master', $editing ? $report->game_master : ($prefill['game_master'] ?? '')) }}</textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="archive-reporter">{{ __('Reporter') }}</label>
    <textarea id="archive-reporter" class="form-control" name="reporter" rows="2" maxlength="2000">{{ old('reporter', $editing ? $report->reporter : ($prefill['reporter'] ?? '')) }}</textarea>
  </div>

  <div class="col-12">
    <label class="form-label" for="archive-players">{{ __('Players') }}</label>
    <textarea id="archive-players" class="form-control" name="players" rows="3" maxlength="10000">{{ old('players', $editing ? $report->players : ($prefill['players'] ?? '')) }}</textarea>
  </div>

  <div class="col-md-6">
    <label class="form-label" for="archive-kia">{{ __('KIA (Killed in Action)') }}</label>
    <textarea id="archive-kia" class="form-control" name="KIA" rows="2" maxlength="5000">{{ old('KIA', $editing ? $report->KIA : '') }}</textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="archive-mia">{{ __('MIA (Missing in Action)') }}</label>
    <textarea id="archive-mia" class="form-control" name="MIA" rows="2" maxlength="5000">{{ old('MIA', $editing ? $report->MIA : '') }}</textarea>
  </div>

  <div class="col-md-6">
    <label class="form-label" for="archive-loot">{{ __('Loot') }}</label>
    <textarea id="archive-loot" class="form-control" name="loot" rows="3" maxlength="10000">{{ old('loot', $editing ? $report->loot : '') }}</textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label" for="archive-expenses">{{ __('Expenses') }}</label>
    <textarea id="archive-expenses" class="form-control" name="expenses" rows="3" maxlength="10000">{{ old('expenses', $editing ? $report->expenses : '') }}</textarea>
  </div>

  <div class="col-12">
    <label class="form-label" for="archive-report">{{ __('Quest report') }} *</label>
    <textarea id="archive-report" class="form-control" name="report" rows="12" maxlength="100000" required>{{ old('report', $editing ? $report->report : ($prefill['report'] ?? '')) }}</textarea>
  </div>
</div>
