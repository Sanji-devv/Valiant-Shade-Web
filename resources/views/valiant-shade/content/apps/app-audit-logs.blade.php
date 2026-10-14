@extends('valiant-shade.layouts/layoutMaster')

@section('title', __('Security Audit Trail - Valiant Shade Admin'))

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/animate-css/animate.scss',
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss'
], 'static/valiant-shade/build')
<style>
  .audit-row:hover {
    background-color: rgba(115, 103, 240, 0.04) !important;
  }
  .diff-pre {
    background: #1e1e2d;
    color: #a6accd;
    border-radius: 8px;
    padding: 12px;
    font-size: 0.82rem;
    max-height: 280px;
    overflow-y: auto;
  }
</style>
@endsection

@section('content')
<!-- Header Banner -->
<div class="card mb-6 border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(115, 103, 240, 0.12) 0%, rgba(40, 199, 111, 0.1) 50%, rgba(234, 84, 85, 0.12) 100%);">
  <div class="card-body p-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-lg">
          <span class="avatar-initial rounded-3 bg-primary text-white shadow-sm fs-2">
            <i class="icon-base ti tabler-history-toggle"></i>
          </span>
        </div>
        <div>
          <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <h4 class="mb-0 fw-bold text-heading">{{ __('Security Audit Trail & Activity Log') }}</h4>
            <span class="badge bg-label-danger font-monospace text-uppercase" style="font-size: 0.72rem;">
              <i class="icon-base ti tabler-lock me-1"></i> {{ __('Admin Only') }}
            </span>
            <span class="badge bg-label-success font-monospace text-uppercase" style="font-size: 0.72rem;">
              <i class="icon-base ti tabler-bolt me-1"></i> {{ __('Spatie Activity Log') }}
            </span>
          </div>
          <p class="mb-0 text-body-secondary small">
            {{ __('Chronological immutable record of all administrative actions, role modifications, permission changes, and security events.') }}
          </p>
        </div>
      </div>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-danger rounded-pill fw-bold" id="btnClearAuditLogs">
          <i class="icon-base ti tabler-trash me-1"></i> {{ __('Clear History') }}
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Metrics Overview -->
<div class="row g-4 mb-6">
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-xs h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="avatar avatar-md">
          <span class="avatar-initial rounded-3 bg-label-primary fs-3">
            <i class="icon-base ti tabler-database"></i>
          </span>
        </div>
        <div>
          <span class="text-body-secondary small d-block">{{ __('Total Events') }}</span>
          <h4 class="mb-0 fw-bold text-heading">{{ $stats['total'] ?? 0 }}</h4>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-xs h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="avatar avatar-md">
          <span class="avatar-initial rounded-3 bg-label-success fs-3">
            <i class="icon-base ti tabler-clock"></i>
          </span>
        </div>
        <div>
          <span class="text-body-secondary small d-block">{{ __('Logged Today') }}</span>
          <h4 class="mb-0 fw-bold text-success">{{ $stats['today'] ?? 0 }}</h4>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-xs h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="avatar avatar-md">
          <span class="avatar-initial rounded-3 bg-label-info fs-3">
            <i class="icon-base ti tabler-users"></i>
          </span>
        </div>
        <div>
          <span class="text-body-secondary small d-block">{{ __('Active Actors') }}</span>
          <h4 class="mb-0 fw-bold text-heading">{{ $stats['unique_actors'] ?? 1 }}</h4>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card border-0 shadow-xs h-100">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="avatar avatar-md">
          <span class="avatar-initial rounded-3 bg-label-warning fs-3">
            <i class="icon-base ti tabler-shield-lock"></i>
          </span>
        </div>
        <div>
          <span class="text-body-secondary small d-block">{{ __('Guest / Anonymous Events') }}</span>
          <h4 class="mb-0 fw-bold text-warning">{{ $stats['guest_events'] ?? 0 }}</h4>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filter Toolbar -->
<div class="card border-0 shadow-xs mb-4">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('valiant-shade.app-audit-logs') }}" class="row g-2 align-items-center">
      <div class="col-12 col-md-3">
        <div class="input-group input-group-sm">
          <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" name="search" class="form-control" placeholder="{{ __('Search summary, actor, IP...') }}" value="{{ $filters['search'] ?? '' }}">
        </div>
      </div>
      <div class="col-6 col-md-2">
        <select name="log_name" class="form-select form-select-sm">
          <option value="">-- {{ __('All Channels') }} --</option>
          @foreach(['model', 'http', 'auth', 'permissions', 'audit'] as $channel)
            <option value="{{ $channel }}" {{ ($filters['log_name'] ?? '') === $channel ? 'selected' : '' }}>{{ strtoupper($channel) }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select name="event" class="form-select form-select-sm">
          <option value="">-- {{ __('All Events') }} --</option>
          <option value="created" {{ ($filters['event'] ?? '') === 'created' ? 'selected' : '' }}>{{ __('Created') }}</option>
          <option value="updated" {{ ($filters['event'] ?? '') === 'updated' ? 'selected' : '' }}>{{ __('Updated') }}</option>
          <option value="deleted" {{ ($filters['event'] ?? '') === 'deleted' ? 'selected' : '' }}>{{ __('Deleted') }}</option>
          <option value="permission." {{ ($filters['event'] ?? '') === 'permission.' ? 'selected' : '' }}>{{ __('Permissions') }}</option>
          <option value="auth." {{ ($filters['event'] ?? '') === 'auth.' ? 'selected' : '' }}>{{ __('Authentication') }}</option>
          <option value="http." {{ ($filters['event'] ?? '') === 'http.' ? 'selected' : '' }}>{{ __('HTTP (mutations)') }}</option>
          <option value="audit." {{ ($filters['event'] ?? '') === 'audit.' ? 'selected' : '' }}>{{ __('Audit Admin') }}</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <input type="text" name="subject_type" class="form-control form-control-sm" placeholder="{{ __('Model (e.g. Character)') }}" value="{{ $filters['subject_type'] ?? '' }}">
      </div>
      <div class="col-6 col-md-1">
        <input type="number" name="subject_id" class="form-control form-control-sm" placeholder="{{ __('Rec. ID') }}" value="{{ $filters['subject_id'] ?? '' }}">
      </div>
      <div class="col-6 col-md-2">
        <input type="text" name="actor" class="form-control form-control-sm" placeholder="{{ __('Actor name / email') }}" value="{{ $filters['actor'] ?? '' }}">
      </div>
      <div class="col-6 col-md-2">
        <input type="text" name="causer_id" class="form-control form-control-sm" placeholder="{{ __('User ID or guest') }}" value="{{ $filters['causer_id'] ?? '' }}">
      </div>
      <div class="col-6 col-md-2">
        <select name="period" class="form-select form-select-sm">
          <option value="">-- {{ __('All time') }} --</option>
          <option value="today" {{ ($filters['period'] ?? '') === 'today' ? 'selected' : '' }}>{{ __('Today') }}</option>
          <option value="week" {{ ($filters['period'] ?? '') === 'week' ? 'selected' : '' }}>{{ __('Last 7 days') }}</option>
          <option value="month" {{ ($filters['period'] ?? '') === 'month' ? 'selected' : '' }}>{{ __('Last 30 days') }}</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <select name="report_period" class="form-select form-select-sm">
          <option value="daily" {{ ($reportPeriod ?? 'daily') === 'daily' ? 'selected' : '' }}>{{ __('Report: Daily') }}</option>
          <option value="weekly" {{ ($reportPeriod ?? '') === 'weekly' ? 'selected' : '' }}>{{ __('Report: Weekly') }}</option>
          <option value="monthly" {{ ($reportPeriod ?? '') === 'monthly' ? 'selected' : '' }}>{{ __('Report: Monthly') }}</option>
        </select>
      </div>
      <div class="col-12 col-md-2 d-flex gap-1 justify-content-end">
        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-3">{{ __('Filter') }}</button>
        <a href="{{ route('valiant-shade.app-audit-logs') }}" class="btn btn-sm btn-outline-secondary rounded-pill">{{ __('Reset') }}</a>
      </div>
    </form>
  </div>
</div>

@if(!empty($periodReport))
<div class="card border-0 shadow-xs mb-4">
  <div class="card-header border-0 pb-0">
    <h6 class="mb-0 fw-bold text-heading">{{ __('Change volume') }} ({{ strtoupper($reportPeriod ?? 'daily') }})</h6>
  </div>
  <div class="card-body pt-3">
    <div class="d-flex flex-wrap gap-2 align-items-end">
      @php $maxTotal = max(1, collect($periodReport)->max('total') ?? 1); @endphp
      @foreach($periodReport as $row)
        @php $h = max(4, (int) round(($row['total'] / $maxTotal) * 72)); @endphp
        <div class="text-center" style="min-width: 48px;">
          <div class="bg-primary rounded-top opacity-75 mx-auto" style="width: 28px; height: {{ $h }}px;"></div>
          <small class="d-block font-monospace text-muted" style="font-size: 0.65rem;">{{ $row['bucket'] }}</small>
          <small class="fw-bold">{{ $row['total'] }}</small>
        </div>
      @endforeach
    </div>
  </div>
</div>
@endif

<!-- Audit Log Table -->
<div class="card border-0 shadow-sm">
  <div class="table-responsive text-nowrap">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>{{ __('Timestamp') }}</th>
          <th>{{ __('Actor') }}</th>
          <th>{{ __('Action') }}</th>
          <th>{{ __('Target') }}</th>
          <th>{{ __('Summary') }}</th>
          <th>{{ __('IP / Origin') }}</th>
          <th class="text-center">{{ __('Details') }}</th>
        </tr>
      </thead>
      <tbody>
        @forelse($logs as $log)
          @php
            $item = is_array($log) ? $log : $log->toArray();
            $action = $item['action'] ?? 'unknown';
            $badgeColor = 'bg-label-primary';
            if (str_contains($action, 'created')) $badgeColor = 'bg-label-success';
            elseif (str_contains($action, 'deleted') || str_contains($action, 'cleared')) $badgeColor = 'bg-label-danger';
            elseif (str_contains($action, 'toggled')) $badgeColor = 'bg-label-warning';
            elseif (str_contains($action, 'updated')) $badgeColor = 'bg-label-info';

            $hasDiff = !empty($item['diff']) && (!empty($item['diff']['before']) || !empty($item['diff']['after']));
          @endphp
          <tr class="audit-row">
            <td>
              <span class="d-block small fw-bold font-monospace text-heading">
                {{ \Carbon\Carbon::parse($item['created_at'] ?? now())->format('Y-m-d H:i:s') }}
              </span>
              <small class="text-muted" style="font-size: 0.72rem;">
                {{ \Carbon\Carbon::parse($item['created_at'] ?? now())->diffForHumans() }}
              </small>
            </td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-xs">
                  <span class="avatar-initial rounded-circle bg-label-dark font-monospace" style="font-size: 10px;">
                    {{ substr($item['actor_name'] ?? 'S', 0, 1) }}
                  </span>
                </span>
                <div>
                  <span class="d-block small fw-bold text-heading">{{ $item['actor_name'] ?? 'System' }}</span>
                  @if(!empty($item['actor_id']))
                    <span class="d-block font-monospace text-muted" style="font-size: 0.68rem;">
                      #{{ $item['actor_id'] }}
                      @if(!empty($item['actor_username']))
                        · {{ $item['actor_username'] }}
                      @elseif(!empty($item['actor_email']))
                        · {{ $item['actor_email'] }}
                      @endif
                    </span>
                  @else
                    <span class="d-block text-muted" style="font-size: 0.68rem;">{{ __('No authenticated user') }}</span>
                  @endif
                  <span class="badge bg-label-dark font-monospace" style="font-size: 0.65rem;">
                    {{ $item['actor_role'] ?? 'SYSTEM' }}
                  </span>
                </div>
              </div>
            </td>
            <td>
              <span class="badge {{ $badgeColor }} rounded-pill font-monospace" style="font-size: 0.72rem;">
                {{ $action }}
              </span>
            </td>
            <td>
              @if(!empty($item['target_type']))
                <span class="badge bg-label-secondary font-monospace" style="font-size: 0.7rem;">
                  {{ $item['target_type'] }} #{{ $item['target_id'] ?? '' }}
                </span>
              @else
                <span class="text-muted small">-</span>
              @endif
            </td>
            <td>
              <span class="small text-heading d-inline-block text-truncate" style="max-width: 320px;" title="{{ $item['summary'] ?? '' }}">
                {{ $item['summary'] ?? '-' }}
              </span>
            </td>
            <td>
              <span class="font-monospace small text-body-secondary d-block" style="font-size: 0.75rem;">
                {{ $item['ip_address'] ?? '127.0.0.1' }}
              </span>
              <small class="text-muted text-truncate d-block" style="max-width: 140px; font-size: 0.7rem;" title="{{ $item['user_agent'] ?? '' }}">
                {{ $item['user_agent'] ?? 'System' }}
              </small>
            </td>
            <td class="text-center">
              @if($hasDiff)
                <button type="button" class="btn btn-xs btn-outline-primary rounded-pill btn-view-diff"
                        data-diff="{{ json_encode($item['diff']) }}"
                        data-summary="{{ $item['summary'] ?? '' }}"
                        data-action="{{ $action }}">
                  <i class="icon-base ti tabler-eye me-1"></i> {{ __('View Diff') }}
                </button>
              @else
                <span class="text-muted small fst-italic" style="font-size: 0.72rem;">{{ __('No Diff') }}</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center py-5">
              <div class="text-body-secondary">
                <i class="icon-base ti tabler-clipboard-check fs-1 text-muted d-block mb-2"></i>
                <p class="mb-0 fw-semibold">{{ __('No audit log records found.') }}</p>
                <small class="text-muted">{{ __('Administrative and security actions will be recorded here automatically.') }}</small>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: View State Diff -->
<div class="modal fade" id="modalAuditDiff" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow-lg">
      <div class="modal-header border-bottom py-3">
        <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2 mb-0">
          <i class="icon-base ti tabler-code text-primary fs-4"></i>
          <span id="diffModalTitle">{{ __('State Change Details') }}</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <div class="modal-body p-4">
        <p class="small text-body-secondary mb-3" id="diffModalSummary"></p>
        <div class="row g-3">
          <div class="col-12 col-md-6">
            <h6 class="fw-bold text-heading mb-1 d-flex align-items-center gap-1">
              <span class="badge bg-label-danger rounded-pill">1</span>
              <span>{{ __('Before State') }}</span>
            </h6>
            <pre class="diff-pre mb-0" id="diffBeforeJson">{}</pre>
          </div>
          <div class="col-12 col-md-6">
            <h6 class="fw-bold text-heading mb-1 d-flex align-items-center gap-1">
              <span class="badge bg-label-success rounded-pill">2</span>
              <span>{{ __('After State') }}</span>
            </h6>
            <pre class="diff-pre mb-0" id="diffAfterJson">{}</pre>
          </div>
        </div>
      </div>
      <div class="modal-footer border-top py-2">
        <button type="button" class="btn btn-sm btn-label-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Close') }}</button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
@vite(['resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.js'], 'static/valiant-shade/build')
<script>
document.addEventListener('DOMContentLoaded', function() {

  // Diff Modal Viewer
  document.querySelectorAll('.btn-view-diff').forEach(btn => {
    btn.addEventListener('click', function() {
      const diffData = JSON.parse(this.dataset.diff || '{}');
      const summary = this.dataset.summary || '';
      const action = this.dataset.action || '';

      document.getElementById('diffModalTitle').textContent = action + ' - ' + '{{ __("State Diff") }}';
      document.getElementById('diffModalSummary').textContent = summary;

      const before = diffData.before || {};
      const after = diffData.after || {};

      document.getElementById('diffBeforeJson').textContent = JSON.stringify(before, null, 2);
      document.getElementById('diffAfterJson').textContent = JSON.stringify(after, null, 2);

      const modal = new bootstrap.Modal(document.getElementById('modalAuditDiff'));
      modal.show();
    });
  });

  // Clear Audit Logs
  const btnClear = document.getElementById('btnClearAuditLogs');
  if (btnClear) {
    btnClear.addEventListener('click', function() {
      Swal.fire({
        title: '{{ __("Clear Audit Logs?") }}',
        text: '{{ __("This will erase all historical administrative logs. This action cannot be undone.") }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '{{ __("Yes, Clear History") }}',
        cancelButtonText: '{{ __("Cancel") }}',
        customClass: {
          confirmButton: 'btn btn-danger me-3 waves-effect waves-light',
          cancelButton: 'btn btn-label-secondary waves-effect waves-light'
        },
        buttonsStyling: false
      }).then((result) => {
        if (result.isConfirmed) {
          fetch('{{ route("valiant-shade.app-audit-logs.clear") }}', {
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json'
            }
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                title: '{{ __("Cleared!") }}',
                text: data.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
              }).then(() => {
                window.location.reload();
              });
            }
          });
        }
      });
    });
  }

});
</script>
@endsection
