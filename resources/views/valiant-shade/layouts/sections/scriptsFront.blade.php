<!-- BEGIN: Vendor JS-->
@vite(['resources/valiant-shade/assets/vendor/js/dropdown-hover.js', 'resources/valiant-shade/assets/vendor/js/mega-dropdown.js', 'resources/valiant-shade/assets/vendor/libs/popper/popper.js', 'resources/valiant-shade/assets/vendor/js/bootstrap.js', 'resources/valiant-shade/assets/vendor/libs/node-waves/node-waves.js'], 'static/valiant-shade/build')

@if ($configData['hasCustomizer'])
  @vite(['resources/valiant-shade/assets/vendor/libs/pickr/pickr.js'], 'static/valiant-shade/build')
@endif

@yield('vendor-script')
<!-- END: Page Vendor JS-->

<!-- BEGIN: Theme JS-->
@vite(['resources/valiant-shade/assets/js/front-main.js'], 'static/valiant-shade/build')
<!-- END: Theme JS-->

<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->

<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->
