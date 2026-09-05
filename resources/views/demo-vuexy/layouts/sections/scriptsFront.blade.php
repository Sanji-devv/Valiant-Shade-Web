<!-- BEGIN: Vendor JS-->
@vite(['resources/demo-vuexy/assets/vendor/js/dropdown-hover.js', 'resources/demo-vuexy/assets/vendor/js/mega-dropdown.js', 'resources/demo-vuexy/assets/vendor/libs/popper/popper.js', 'resources/demo-vuexy/assets/vendor/js/bootstrap.js', 'resources/demo-vuexy/assets/vendor/libs/node-waves/node-waves.js'], 'static/demo-vuexy/build')

@if ($configData['hasCustomizer'])
  @vite(['resources/demo-vuexy/assets/vendor/libs/pickr/pickr.js'], 'static/demo-vuexy/build')
@endif

@yield('vendor-script')
<!-- END: Page Vendor JS-->

<!-- BEGIN: Theme JS-->
@vite(['resources/demo-vuexy/assets/js/front-main.js'], 'static/demo-vuexy/build')
<!-- END: Theme JS-->

<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->

<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->
