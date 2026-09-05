<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
  href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
  rel="stylesheet" />

@vite(['resources/demo-vuexy/assets/vendor/fonts/iconify/iconify.css'], 'static/demo-vuexy/build')

@if ($configData['hasCustomizer'])
  @vite(['resources/demo-vuexy/assets/vendor/libs/pickr/pickr-themes.scss'], 'static/demo-vuexy/build')
@endif

<!-- Vendor Styles -->
@yield('vendor-style')
@vite(['resources/demo-vuexy/assets/vendor/libs/node-waves/node-waves.scss'], 'static/demo-vuexy/build')

<!-- Core CSS -->
@vite(['resources/demo-vuexy/assets/vendor/scss/core.scss', 'resources/demo-vuexy/assets/css/demo.css', 'resources/demo-vuexy/assets/vendor/scss/pages/front-page.scss'], 'static/demo-vuexy/build')

<!-- Page Styles -->
@yield('page-style')
