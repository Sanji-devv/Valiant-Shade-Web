<!-- BEGIN: Vendor JS-->

@vite(['resources/admin/assets/vendor/libs/jquery/jquery.js', 'resources/admin/assets/vendor/libs/popper/popper.js', 'resources/admin/assets/vendor/js/bootstrap.js', 'resources/admin/assets/vendor/libs/node-waves/node-waves.js'], 'static/admin/build')

@if ($configData['hasCustomizer'])
  @vite('resources/admin/assets/vendor/libs/pickr/pickr.js', 'static/admin/build')
@endif

@vite(['resources/admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js', 'resources/admin/assets/vendor/libs/hammer/hammer.js', 'resources/admin/assets/vendor/js/menu.js'], 'static/admin/build')

@yield('vendor-script')
<!-- END: Page Vendor JS-->

<!-- BEGIN: Theme JS-->
@vite(['resources/admin/assets/js/main.js'], 'static/admin/build')
<!-- END: Theme JS-->

<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->

<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->

<!-- app JS -->
@vite(['resources/admin/js/app.js'], 'static/admin/build')
<!-- END: app JS-->
