@php
    use Illuminate\Support\Facades\Route;
    $configData = \App\ValiantShade\Helpers\Helpers::appClasses();
@endphp
<!-- Horizontal Menu -->
<aside id="layout-menu" class="layout-menu-horizontal menu-horizontal  menu bg-menu-theme flex-grow-0"
    @foreach ($configData['menuAttributes'] as $attribute => $value)
  {{ $attribute }}="{{ $value }}" @endforeach>
    <div class="{{ $containerNav }} d-flex h-100">
        <ul class="menu-inner">
            @foreach ($menuData[1]->menu as $menu)
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
                                $activeClass = 'active';
                                break;
                            }
                        }
                    }
                @endphp

                {{-- main menu --}}
                <li class="menu-item {{ $activeClass }}">
                    <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
                        class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
                        @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
                        @isset($menu->icon)
                            <i class="{{ $menu->icon }}"></i>
                        @endisset
                        <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
                    </a>

                    {{-- submenu --}}
                    @isset($menu->submenu)
                        @include('valiant-shade.layouts.sections.menu.submenu', ['menu' => $menu->submenu])
                    @endisset
                </li>
            @endforeach
        </ul>
    </div>
</aside>
<!--/ Horizontal Menu -->
