@php
$containerFooter =
isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact'
? 'container-xxl'
: 'container-fluid';
@endphp

<!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
  <div class="{{ $containerFooter }}">
    <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
      <div class="text-body">
        &#169;
        <script>
          document.write(new Date().getFullYear());
        </script>
        , made with ❤️ by <a href="{{ !empty(config('admin-variables.creatorUrl')) ? config('admin-variables.creatorUrl') : '' }}" target="_blank" class="footer-link">{{ !empty(config('admin-variables.creatorName')) ? config('admin-variables.creatorName') : '' }}</a>
      </div>
      <div class="d-none d-lg-inline-block">
        <a href="{{ config('admin-variables.licenseUrl') ? config('admin-variables.licenseUrl') : '#' }}" class="footer-link me-4" target="_blank">License</a>
        <a href="{{ config('admin-variables.moreThemes') ? config('admin-variables.moreThemes') : '#' }}" target="_blank" class="footer-link me-4">More Themes</a>
        <a href="{{ config('admin-variables.documentation') ? config('admin-variables.documentation') . '/laravel-introduction.html' : '#' }}" target="_blank" class="footer-link me-4">Documentation</a>
        <a href="{{ config('admin-variables.support') ? config('admin-variables.support') : '#' }}" target="_blank" class="footer-link">Support</a>
      </div>
    </div>
  </div>
</footer>
<!-- / Footer -->
