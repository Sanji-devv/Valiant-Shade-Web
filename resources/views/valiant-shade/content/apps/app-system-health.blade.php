@extends('valiant-shade.layouts/layoutMaster')

@section('title', __('System Health - Valiant Shade Admin'))

@section('content')
@php
  $statusBadge = function (array $check): array {
    return match ($check['status']) {
      'ok' => ['success', __('Healthy'), 'tabler-circle-check'],
      'warning' => ['warning', __('Warning'), 'tabler-alert-triangle'],
      'skipped' => ['secondary', __('Skipped'), 'tabler-player-skip-forward'],
      default => ['danger', __('Unhealthy'), 'tabler-circle-x'],
    };
  };
@endphp

<div class="card mb-6 border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(40, 199, 111, 0.12) 0%, rgba(115, 103, 240, 0.1) 50%, rgba(234, 84, 85, 0.12) 100%);">
  <div class="card-body p-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-lg">
          <span class="avatar-initial rounded-3 bg-success text-white shadow-sm fs-2">
            <i class="icon-base ti tabler-heartbeat"></i>
          </span>
        </div>
        <div>
          <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <h4 class="mb-0 fw-bold text-heading">{{ __('System Health Dashboard') }}</h4>
            <span class="badge bg-label-danger font-monospace text-uppercase" style="font-size: 0.72rem;">
              <i class="icon-base ti tabler-lock me-1"></i> {{ __('Admin Only') }}
            </span>
            <span class="badge bg-label-primary font-monospace text-uppercase" style="font-size: 0.72rem;">
              <i class="icon-base ti tabler-bolt me-1"></i> {{ __('Spatie Laravel Health') }}
            </span>
          </div>
          <p class="mb-0 text-body-secondary small">
            {{ __('Live infrastructure checks: CPU, disk, database, cache, debug mode, production environment, and optimization status.') }}
          </p>
        </div>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('valiant-shade.app-health') }}" class="btn btn-primary rounded-pill">
          <i class="icon-base ti tabler-refresh me-1"></i> {{ __('Refresh') }}
        </a>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-6">
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-xs h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="avatar avatar-md">
          <span class="avatar-initial rounded-3 bg-label-success fs-3">
            <i class="icon-base ti tabler-circle-check"></i>
          </span>
        </div>
        <div>
          <span class="text-body-secondary small d-block">{{ __('Healthy') }}</span>
          <h4 class="mb-0 fw-bold text-success">{{ $summary['ok'] ?? 0 }}</h4>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-xs h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="avatar avatar-md">
          <span class="avatar-initial rounded-3 bg-label-warning fs-3">
            <i class="icon-base ti tabler-alert-triangle"></i>
          </span>
        </div>
        <div>
          <span class="text-body-secondary small d-block">{{ __('Warnings') }}</span>
          <h4 class="mb-0 fw-bold text-warning">{{ $summary['warning'] ?? 0 }}</h4>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-xs h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="avatar avatar-md">
          <span class="avatar-initial rounded-3 bg-label-danger fs-3">
            <i class="icon-base ti tabler-circle-x"></i>
          </span>
        </div>
        <div>
          <span class="text-body-secondary small d-block">{{ __('Failed') }}</span>
          <h4 class="mb-0 fw-bold text-danger">{{ $summary['failed'] ?? 0 }}</h4>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-xs h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="avatar avatar-md">
          <span class="avatar-initial rounded-3 bg-label-{{ ($summary['healthy'] ?? false) ? 'success' : 'danger' }} fs-3">
            <i class="icon-base ti tabler-{{ ($summary['healthy'] ?? false) ? 'shield-check' : 'shield-x' }}"></i>
          </span>
        </div>
        <div>
          <span class="text-body-secondary small d-block">{{ __('Overall') }}</span>
          <h4 class="mb-0 fw-bold text-heading">{{ ($summary['healthy'] ?? false) ? __('All clear') : __('Issues detected') }}</h4>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card mb-6 border-0 shadow-sm">
  <div class="card-header border-0 pb-0">
    <h5 class="mb-0">{{ __('Runtime') }}</h5>
  </div>
  <div class="card-body pt-3">
    <div class="row g-3">
      @foreach ([
        __('Application') => $runtime['app_name'] ?? '-',
        __('Environment (APP_ENV)') => $runtime['app_env'] ?? '-',
        __('Debug (APP_DEBUG)') => ($runtime['app_debug'] ?? false) ? __('Enabled') : __('Disabled'),
        __('PHP') => $runtime['php_version'] ?? '-',
        __('Laravel') => $runtime['laravel_version'] ?? '-',
        __('Checked at') => $runtime['server_time'] ?? '-',
      ] as $label => $value)
        <div class="col-md-4 col-lg-2">
          <div class="border rounded-3 p-3 h-100">
            <div class="text-body-secondary small mb-1">{{ $label }}</div>
            <div class="fw-semibold text-heading">{{ $value }}</div>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h5 class="mb-0">{{ __('Health Checks') }}</h5>
    <span class="text-body-secondary small">{{ __('Green = OK, yellow = warning, red = failed') }}</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th>{{ __('Status') }}</th>
          <th>{{ __('Check') }}</th>
          <th>{{ __('Summary') }}</th>
          <th>{{ __('Details') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($results as $check)
          @php [$tone, $statusLabel, $icon] = $statusBadge($check); @endphp
          <tr>
            <td>
              <span class="badge bg-{{ $tone }} rounded-pill px-3 py-2">
                <i class="icon-base ti {{ $icon }} me-1"></i>{{ $statusLabel }}
              </span>
            </td>
            <td class="fw-semibold">{{ __($check['label']) }}</td>
            <td>{{ $check['short_summary'] ?: '—' }}</td>
            <td class="small text-body-secondary">
              @if (! empty($check['message']))
                <div class="text-{{ $check['is_failed'] ? 'danger' : ($check['is_warning'] ? 'warning' : 'body') }}">{{ $check['message'] }}</div>
              @endif
              @if (! empty($check['meta']))
                <ul class="mb-0 ps-3">
                  @foreach ($check['meta'] as $key => $value)
                    <li><span class="text-heading">{{ str_replace('_', ' ', (string) $key) }}:</span> {{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}</li>
                  @endforeach
                </ul>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="4" class="text-center text-body-secondary py-5">{{ __('No health checks are registered.') }}</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
