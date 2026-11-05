<!DOCTYPE html>
@php
  use Illuminate\Support\Str;
  use App\ValiantShade\Helpers\Helpers;

  $menuFixed =
      $configData['layout'] === 'vertical'
          ? $menuFixed ?? ''
          : ($configData['layout'] === 'front'
              ? ''
              : $configData['headerType']);
  $navbarType =
      $configData['layout'] === 'vertical'
          ? $configData['navbarType']
          : ($configData['layout'] === 'front'
              ? 'layout-navbar-fixed'
              : '');
  $isFront = ($isFront ?? '') == true ? 'Front' : '';
  $contentLayout = isset($container) ? ($container === 'container-xxl' ? 'layout-compact' : 'layout-wide') : '';

  // Get skin name from configData - only applies to admin layouts
  $isAdminLayout = !Str::contains($configData['layout'] ?? '', 'front');
  $skinName = $isAdminLayout ? $configData['skinName'] ?? 'default' : 'default';

  // Get semiDark value from configData - only applies to admin layouts
  $semiDarkEnabled = $isAdminLayout && filter_var($configData['semiDark'] ?? false, FILTER_VALIDATE_BOOLEAN);

  // Generate primary color CSS if color is set
  $primaryColorCSS = '';
  if (isset($configData['color']) && $configData['color']) {
      $primaryColorCSS = Helpers::generatePrimaryColorCSS($configData['color']);
  }

@endphp

<html lang="{{ session()->get('locale') ?? app()->getLocale() }}"
  class="{{ $navbarType ?? '' }} {{ $contentLayout ?? '' }} {{ $menuFixed ?? '' }} {{ $menuCollapsed ?? '' }} {{ $footerFixed ?? '' }} {{ $customizerHidden ?? '' }}"
  dir="{{ $configData['textDirection'] }}" data-skin="{{ $skinName }}" data-assets-path="{{ asset('/static/demo-vuexy/assets') . '/' }}"
  data-base-url="{{ url('/valiant-shade') }}" data-framework="laravel" data-template="{{ $configData['layout'] }}-menu-template"
  data-user-id="{{ Auth::id() ?? '' }}"
  data-bs-theme="{{ $configData['theme'] }}" @if ($isAdminLayout && $semiDarkEnabled) data-semidark-menu="true" @endif>

<head>
  <meta charset="utf-8" />
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />

  <title>
    @yield('title') | {{ config('valiant-shade-variables.templateName') ? config('valiant-shade-variables.templateName') : 'TemplateName' }}
    - {{ config('valiant-shade-variables.templateSuffix') ? config('valiant-shade-variables.templateSuffix') : 'TemplateSuffix' }}
  </title>
  <meta name="description"
    content="{{ config('valiant-shade-variables.templateDescription') ? config('valiant-shade-variables.templateDescription') : '' }}" />
  <meta name="keywords"
    content="{{ config('valiant-shade-variables.templateKeyword') ? config('valiant-shade-variables.templateKeyword') : '' }}" />
  <meta property="og:title" content="{{ config('valiant-shade-variables.ogTitle') ? config('valiant-shade-variables.ogTitle') : '' }}" />
  <meta property="og:type" content="{{ config('valiant-shade-variables.ogType') ? config('valiant-shade-variables.ogType') : '' }}" />
  <meta property="og:url" content="{{ config('valiant-shade-variables.productPage') ? config('valiant-shade-variables.productPage') : '' }}" />
  <meta property="og:image" content="{{ config('valiant-shade-variables.ogImage') ? config('valiant-shade-variables.ogImage') : '' }}" />
  <meta property="og:description"
    content="{{ config('valiant-shade-variables.templateDescription') ? config('valiant-shade-variables.templateDescription') : '' }}" />
  <meta property="og:site_name"
    content="{{ config('valiant-shade-variables.creatorName') ? config('valiant-shade-variables.creatorName') : '' }}" />
  <meta name="robots" content="noindex, nofollow" />
  <!-- laravel CRUD token -->
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <!-- Canonical SEO -->
  <link rel="canonical" href="{{ config('valiant-shade-variables.productPage') ? config('valiant-shade-variables.productPage') : '' }}" />
  <!-- Favicon -->
  <link rel="icon" type="image/png" href="{{ asset('static/valiant-shade/assets/img/favicon/favicon.png') }}" />
  <link rel="apple-touch-icon" href="{{ asset('static/valiant-shade/assets/img/branding/valiant-shade-logo-small.png') }}" />

  <!-- Include Styles -->
  <!-- $isFront is used to append the front layout styles only on the front layout otherwise the variable will be blank -->
  @include('valiant-shade.layouts/sections/styles' . $isFront)

  @if (
      $primaryColorCSS &&
          (config('custom.custom.primaryColor') ||
              isset($_COOKIE['admin-primaryColor']) ||
              isset($_COOKIE['front-primaryColor'])))
    <!-- Primary Color Style -->
    <style id="primary-color-style">
      {!! $primaryColorCSS !!}
    </style>
  @endif

  <!-- Include Scripts for customizer, helper, analytics, config -->
  <!-- $isFront is used to append the front layout scriptsIncludes only on the front layout otherwise the variable will be blank -->
  @include('valiant-shade.layouts/sections/scriptsIncludes' . $isFront)
</head>

<body>
  <!-- Layout Content -->
  @yield('layoutContent')
  <!--/ Layout Content -->

  

  <!-- Include Scripts -->
  <!-- $isFront is used to append the front layout scripts only on the front layout otherwise the variable will be blank -->
  @include('valiant-shade.layouts/sections/scripts' . $isFront)

</body>

</html>
