<!-- BEGIN: Vendor JS-->

@vite(['resources/demo-vuexy/assets/vendor/libs/jquery/jquery.js', 'resources/demo-vuexy/assets/vendor/libs/popper/popper.js', 'resources/demo-vuexy/assets/vendor/js/bootstrap.js', 'resources/demo-vuexy/assets/vendor/libs/node-waves/node-waves.js', 'resources/demo-vuexy/assets/vendor/libs/@algolia/autocomplete-js.js'], 'static/demo-vuexy/build')

@if ($configData['hasCustomizer'])
  @vite('resources/demo-vuexy/assets/vendor/libs/pickr/pickr.js', 'static/demo-vuexy/build')
@endif

@vite(['resources/demo-vuexy/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js', 'resources/demo-vuexy/assets/vendor/libs/hammer/hammer.js', 'resources/demo-vuexy/assets/vendor/js/menu.js'], 'static/demo-vuexy/build')

@yield('vendor-script')
<!-- END: Page Vendor JS-->

<!-- BEGIN: Theme JS-->
@vite(['resources/demo-vuexy/assets/js/main.js'], 'static/demo-vuexy/build')
<!-- END: Theme JS-->

<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->

<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->

<!-- app JS -->
@vite(['resources/demo-vuexy/js/app.js'], 'static/demo-vuexy/build')
<!-- END: app JS-->
