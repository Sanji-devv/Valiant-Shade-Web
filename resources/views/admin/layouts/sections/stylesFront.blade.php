<!-- BEGIN: Theme CSS-->
<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link
  href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&ampdisplay=swap"
  rel="stylesheet" />

@vite(['resources/admin/assets/vendor/fonts/iconify/iconify.css'], 'static/admin/build')

@if ($configData['hasCustomizer'])
  @vite(['resources/admin/assets/vendor/libs/pickr/pickr-themes.scss'], 'static/admin/build')
@endif

<!-- Vendor Styles -->
@yield('vendor-style')
@vite(['resources/admin/assets/vendor/libs/node-waves/node-waves.scss'], 'static/admin/build')

<!-- Core CSS -->
@vite(['resources/admin/assets/vendor/scss/core.scss', 'resources/admin/assets/css/demo.css', 'resources/admin/assets/vendor/scss/pages/front-page.scss'], 'static/admin/build')

<!-- Page Styles -->
@yield('page-style')
