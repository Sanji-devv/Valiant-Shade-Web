@php
use Illuminate\Support\Facades\Route;
@endphp

<ul class="menu-sub">
  @if (isset($menu))
    @foreach ($menu as $submenu)
    @php
      $isLoginSub = ($submenu->slug === 'auth-login-basic');
      $isRegisterSub = ($submenu->slug === 'auth-register-basic');
      $isAdminOnlySub = (
        $submenu->slug === 'app-roles' ||
        $submenu->slug === 'app-audit-logs' ||
        (isset($submenu->url) && (str_contains($submenu->url, '/app/roles') || str_contains($submenu->url, '/app/audit-logs')))
      );
    @endphp

    @if (Auth::check() && $isRegisterSub)
      @continue
    @endif

    @if ($isAdminOnlySub && (!Auth::check() || !Auth::user()->isAdmin()))
      @continue
    @endif

    @php
      $isLogoutSub = Auth::check() && $isLoginSub;
      $subUrl = $isLogoutSub ? route('logout') : (isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)');
      $subName = $isLogoutSub ? __('Logout') : (isset($submenu->name) ? __($submenu->name) : '');
      $subIcon = $isLogoutSub ? 'menu-icon icon-base ti tabler-logout text-danger' : ($submenu->icon ?? null);
    @endphp

    {{-- active menu method --}}
    @php
      $activeClass = null;
      $active = $configData["layout"] === 'vertical' ? 'active open':'active';
      $currentRouteName = Route::currentRouteName() ?? '';
      $routeWithoutPrefix = str_starts_with($currentRouteName, 'valiant-shade.') ? substr($currentRouteName, 14) : $currentRouteName;

      if ($currentRouteName === $submenu->slug || $routeWithoutPrefix === $submenu->slug) {
          $activeClass = 'active';
      }
      elseif (isset($submenu->submenu)) {
        $slugs = is_array($submenu->slug) ? $submenu->slug : [$submenu->slug];
        foreach ($slugs as $slug) {
          if (str_starts_with($routeWithoutPrefix, $slug) || str_starts_with($currentRouteName, $slug)) {
              $activeClass = $active;
              break;
          }
        }
      }
    @endphp

      <li class="menu-item {{$activeClass}}">
        <a href="{{ $subUrl }}"
          class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }} {{ $isLogoutSub ? 'text-danger' : '' }}"
          @if ($isLogoutSub) onclick="event.preventDefault(); document.getElementById('submenu-logout-form').submit();" @endif
          @if (isset($submenu->target) and !empty($submenu->target) and !$isLogoutSub) target="_blank" @endif>
          @if ($subIcon)
          <i class="{{ $subIcon }}"></i>
          @endif
          <div>{{ $subName }}</div>
          @if (!$isLogoutSub && isset($submenu->badge))
            <div class="badge bg-{{ $submenu->badge[0] }} rounded-pill ms-auto">{{ $submenu->badge[1] }}</div>
          @endif
        </a>

        {{-- submenu --}}
        @if (isset($submenu->submenu))
          @include('valiant-shade.layouts.sections.menu.submenu',['menu' => $submenu->submenu])
        @endif
      </li>
    @endforeach
    @if (Auth::check())
      <form id="submenu-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
      </form>
    @endif
  @endif
</ul>
