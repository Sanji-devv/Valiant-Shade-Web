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

  @guest
  <!-- Guest Interaction Guard: Allows page browsing, redirects to login on any interaction -->
  <script>
    (function () {
      const loginUrl = "{{ route('valiant-shade.auth-login-basic') }}";

      function isAuthPage() {
        return Boolean(document.querySelector('#formAuthentication')) ||
               window.location.pathname.includes('/valiant-shade/auth/');
      }

      function redirectToLogin(event) {
        if (isAuthPage()) return;
        if (event) {
          event.preventDefault();
          event.stopPropagation();
          if (typeof event.stopImmediatePropagation === 'function') {
            event.stopImmediatePropagation();
          }
        }
        window.location.href = loginUrl;
      }

      // 1. Intercept all non-auth form submissions
      document.addEventListener('submit', function (e) {
        if (isAuthPage()) return;
        if (e.target && e.target.id === 'formAuthentication') return;
        redirectToLogin(e);
      }, true);

      // 2. Intercept button / action clicks
      document.addEventListener('click', function (e) {
        if (isAuthPage()) return;

        const target = e.target.closest('a, button, input, select, textarea, .btn');
        if (!target) return;

        // Inside auth form? Allow
        if (target.closest('#formAuthentication')) return;

        // Theme switcher toggle & options -> allow for theme previewing
        if (
          target.closest('#nav-theme') ||
          target.closest('[data-bs-theme-value]') ||
          target.matches('[data-bs-theme-value]')
        ) {
          return;
        }

        // Language switcher -> allow for language previewing
        if (target.closest('.dropdown-language') || target.matches('.dropdown-language a')) {
          return;
        }

        // Search bar in navbar / modal -> allow for navigating pages
        if (
          target.closest('.search-toggler') ||
          target.closest('.navbar-search-wrapper') ||
          target.closest('.twitter-typeahead') ||
          target.closest('.search-input-wrapper')
        ) {
          return;
        }

        // Sidebar and layout navigation toggles
        if (
          target.closest('.layout-menu-toggle') ||
          target.closest('.menu-toggle') ||
          target.closest('.menu-link') ||
          target.matches('.layout-menu-toggle, .menu-toggle')
        ) {
          return;
        }

        // Bootstrap browsing toggles (tabs, pills, collapse accordions, dropdown triggers)
        const isBrowseToggle =
          target.getAttribute('data-bs-toggle') === 'dropdown' ||
          target.getAttribute('data-bs-toggle') === 'tab' ||
          target.getAttribute('data-bs-toggle') === 'pill' ||
          target.getAttribute('data-bs-toggle') === 'collapse' ||
          target.closest('[data-bs-toggle="dropdown"]') ||
          target.closest('[data-bs-toggle="tab"]') ||
          target.closest('[data-bs-toggle="pill"]') ||
          target.closest('[data-bs-toggle="collapse"]');

        if (isBrowseToggle) {
          return;
        }

        // Table pagination links -> browsing data
        if (target.closest('.pagination') || target.matches('.page-link')) {
          return;
        }

        // Anchor links
        if (target.tagName === 'A') {
          const href = target.getAttribute('href');
          // If href is a real page link (e.g. /valiant-shade/tables/basic or https://...)
          if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
            return;
          }
          // Non-navigation anchor (action button disguised as <a> with href="javascript:void(0)" or #)
          redirectToLogin(e);
          return;
        }

        // Any button, input, select, textarea or .btn clicked outside auth
        if (
          target.tagName === 'BUTTON' ||
          target.tagName === 'INPUT' ||
          target.tagName === 'SELECT' ||
          target.tagName === 'TEXTAREA' ||
          target.classList.contains('btn')
        ) {
          redirectToLogin(e);
        }
      }, true);

      // 3. Intercept typing into form inputs outside auth forms
      document.addEventListener('keydown', function (e) {
        if (isAuthPage()) return;
        const target = e.target.closest('input, select, textarea');
        if (!target) return;
        if (target.closest('#formAuthentication')) return;
        if (target.closest('.search-input') || target.closest('.navbar-search-wrapper')) return;
        redirectToLogin(e);
      }, true);
    })();
  </script>
  @endguest
</body>

</html>
