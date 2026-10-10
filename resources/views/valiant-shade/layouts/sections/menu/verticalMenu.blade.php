@php
use Illuminate\Support\Facades\Route;
$configData = \App\ValiantShade\Helpers\Helpers::appClasses();
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu" @foreach ($configData['menuAttributes'] as $attribute=>
  $value)
  {{ $attribute }}="{{ $value }}" @endforeach>

  <!-- ! Hide app brand if navbar-full -->
  @if (!isset($navbarFull))
  <div class="app-brand demo">
    <a href="{{ url('/valiant-shade') }}" class="app-brand-link">
      <span class="app-brand-logo demo">@include('valiant-shade._partials.macros')</span>
      <span class="app-brand-text demo menu-text fw-bold ms-3">{{ config('valiant-shade-variables.templateName') }}</span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
      <i class="icon-base ti tabler-x d-block d-xl-none"></i>
    </a>
  </div>
  @endif

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    @foreach ($menuData[0]->menu as $menu)
    {{-- adding active and open class if child is active --}}

    {{-- menu headers --}}
    @if (isset($menu->menuHeader))
    <li class="menu-header small">
      <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
    </li>
    @else
    {{-- active menu method --}}
    @php
    $activeClass = null;
    $currentRouteName = Route::currentRouteName() ?? '';
    $routeWithoutPrefix = str_starts_with($currentRouteName, 'valiant-shade.') ? substr($currentRouteName, 14) : $currentRouteName;

    if ($currentRouteName === $menu->slug || $routeWithoutPrefix === $menu->slug || (isset($menu->url) && url()->current() === url($menu->url))) {
      $activeClass = 'active';
    } elseif (isset($menu->submenu)) {
      $slugs = is_array($menu->slug) ? $menu->slug : [$menu->slug];
      foreach ($slugs as $slug) {
        if (str_starts_with($routeWithoutPrefix, $slug) || str_starts_with($currentRouteName, $slug)) {
          if ($slug === 'dashboard' && $routeWithoutPrefix === 'dashboard-analytics') {
            continue;
          }
          $activeClass = 'active open';
          break;
        }
      }
    }
    @endphp

    @php
      $isLoginSlug = ($menu->slug === 'auth-login-basic');
      $isRegisterSlug = ($menu->slug === 'auth-register-basic');
    @endphp

    @if (Auth::check() && $isRegisterSlug)
      @continue
    @endif

    @php
      $isLogoutItem = Auth::check() && $isLoginSlug;
      $menuUrl = $isLogoutItem ? route('logout') : (isset($menu->url) ? url($menu->url) : 'javascript:void(0);');
      $menuName = $isLogoutItem ? __('Logout') : (isset($menu->name) ? __($menu->name) : '');
      $menuIcon = $isLogoutItem ? 'menu-icon icon-base ti tabler-logout text-danger' : ($menu->icon ?? null);
    @endphp

    {{-- main menu --}}
    <li class="menu-item {{ $activeClass }}">
      <a href="{{ $menuUrl }}"
        class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }} {{ $isLogoutItem ? 'text-danger' : '' }}"
        @if ($isLogoutItem) onclick="event.preventDefault(); document.getElementById('vertical-menu-logout-form').submit();" @endif
        @if (isset($menu->target) and !empty($menu->target) and !$isLogoutItem) target="_blank" @endif>
        @isset($menuIcon)
        <i class="{{ $menuIcon }}"></i>
        @endisset
        <div>{{ $menuName }}</div>
        @if (!$isLogoutItem && isset($menu->badge))
        <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
        @endif
      </a>

      {{-- submenu --}}
      @isset($menu->submenu)
      @include('valiant-shade.layouts.sections.menu.submenu', ['menu' => $menu->submenu])
      @endisset
    </li>
    @endif
    @endforeach
  </ul>

  @if (Auth::check())
  <form id="vertical-menu-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
  </form>
  @endif

</aside>