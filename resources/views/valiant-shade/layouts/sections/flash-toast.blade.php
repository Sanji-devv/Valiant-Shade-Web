@php
  $flashMessage = session('success') ?? session('error') ?? session('warning') ?? session('info');
  $flashType = session('success') ? 'success' : (session('error') ? 'danger' : (session('warning') ? 'warning' : (session('info') ? 'info' : null)));
  if (session('toast') && is_array(session('toast'))) {
      $flashMessage = session('toast.message') ?? $flashMessage;
      $flashType = session('toast.type') ?? $flashType;
  }
@endphp
@if ($flashMessage && $flashType)
  <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1095;">
    <div id="vsFlashToast" class="toast align-items-center text-bg-{{ $flashType }} border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5500">
      <div class="d-flex">
        <div class="toast-body fw-medium">{{ $flashMessage }}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="{{ __('Close') }}"></button>
      </div>
    </div>
  </div>
  @push('pricing-script')
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const el = document.getElementById('vsFlashToast');
      if (el && typeof bootstrap !== 'undefined' && bootstrap.Toast) {
        bootstrap.Toast.getOrCreateInstance(el).show();
      }
    });
  </script>
  @endpush
@endif
