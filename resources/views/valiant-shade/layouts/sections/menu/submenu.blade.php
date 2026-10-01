@php
use Illuminate\Support\Facades\Route;
@endphp

<ul class="menu-sub">
  @if (isset($menu))
    @foreach ($menu as $submenu)

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
        <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}" @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
          @if (isset($submenu->icon))
          <i class="{{ $submenu->icon }}"></i>
          @endif
          <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
          @isset($submenu->badge)
            <div class="badge bg-{{ $submenu->badge[0] }} rounded-pill ms-auto">{{ $submenu->badge[1] }}</div>
          @endisset
        </a>

        {{-- submenu --}}
        @if (isset($submenu->submenu))
          @include('valiant-shade.layouts.sections.menu.submenu',['menu' => $submenu->submenu])
        @endif
      </li>
    @endforeach
  @endif
</ul>
