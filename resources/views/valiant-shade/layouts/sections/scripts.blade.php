<!-- BEGIN: Vendor JS-->

@vite(['resources/valiant-shade/assets/vendor/libs/jquery/jquery.js', 'resources/valiant-shade/assets/vendor/libs/popper/popper.js', 'resources/valiant-shade/assets/vendor/js/bootstrap.js', 'resources/valiant-shade/assets/vendor/libs/node-waves/node-waves.js', 'resources/valiant-shade/assets/vendor/libs/@algolia/autocomplete-js.js'], 'static/valiant-shade/build')

@if ($configData['hasCustomizer'])
  @vite('resources/valiant-shade/assets/vendor/libs/pickr/pickr.js', 'static/valiant-shade/build')
@endif

@vite(['resources/valiant-shade/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js', 'resources/valiant-shade/assets/vendor/libs/hammer/hammer.js', 'resources/valiant-shade/assets/vendor/js/menu.js'], 'static/valiant-shade/build')

@yield('vendor-script')
<!-- END: Page Vendor JS-->

<!-- BEGIN: Theme JS-->
@vite(['resources/valiant-shade/assets/js/main.js'], 'static/valiant-shade/build')
<!-- END: Theme JS-->

<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->

<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->

<!-- app JS -->
@vite(['resources/valiant-shade/js/app.js'], 'static/valiant-shade/build')
<!-- END: app JS-->
