<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
  href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
  rel="stylesheet" />

@vite(['resources/valiant-shade/assets/vendor/fonts/iconify/iconify.css'], 'static/valiant-shade/build')

@if ($configData['hasCustomizer'])
  @vite(['resources/valiant-shade/assets/vendor/libs/pickr/pickr-themes.scss'], 'static/valiant-shade/build')
@endif

<!-- Vendor Styles -->
@yield('vendor-style')
@vite(['resources/valiant-shade/assets/vendor/libs/node-waves/node-waves.scss'], 'static/valiant-shade/build')

<!-- Core CSS -->
@vite(['resources/valiant-shade/assets/vendor/scss/core.scss', 'resources/valiant-shade/assets/css/demo.css', 'resources/valiant-shade/assets/vendor/scss/pages/front-page.scss'], 'static/valiant-shade/build')

<!-- Page Styles -->
@yield('page-style')
