@extends('valiant-shade.layouts/layoutMaster')

@section('title', __('Roles & Permissions - Valiant Shade Admin'))

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/animate-css/animate.scss',
  'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss'
], 'static/valiant-shade/build')
<style>
  /* Roles & Permissions Custom Styles */
  .role-card {
    transition: transform 0.22s ease, box-shadow 0.22s ease;
    border: 1px solid rgba(255, 255, 255, 0.08);
  }
  .role-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25) !important;
  }
  .role-badge-icon {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    font-size: 1.35rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }
  .matrix-table th {
    white-space: nowrap;
    vertical-align: middle;
  }
  .matrix-table td {
    vertical-align: middle;
  }
  .matrix-table th.bailiff-header-col {
    background: rgba(255, 159, 67, 0.06);
    border-left: 1px solid rgba(255, 159, 67, 0.2);
    border-right: 1px solid rgba(255, 159, 67, 0.2);
  }
  .matrix-table td.bailiff-body-col {
    background: rgba(255, 159, 67, 0.02);
    border-left: 1px solid rgba(255, 159, 67, 0.12);
    border-right: 1px solid rgba(255, 159, 67, 0.12);
  }
  .matrix-switch {
    cursor: pointer;
  }
  .roles-main-card .nav-tabs .nav-link {
    font-weight: 600;
  }
  .matrix-row-hidden,
  .role-item-col.role-card-hidden {
    display: none !important;
  }
  .role-filter-list .list-group-item {
    border: 0;
    border-radius: 0.5rem;
    margin-bottom: 0.35rem;
    padding: 0.65rem 0.85rem;
    font-weight: 500;
  }
  .role-filter-list .list-group-item.active {
    background: var(--bs-primary);
    color: #fff;
  }
  .role-filter-list .list-group-item.active .badge {
    background: rgba(255, 255, 255, 0.25) !important;
    color: #fff !important;
  }
  .role-filter-list .list-group-item:not(.active):hover {
    background: rgba(var(--bs-primary-rgb), 0.08);
  }
  .roles-cards-sidebar {
    background: rgba(var(--bs-body-bg-rgb), 0.6);
  }
  @media (min-width: 992px) {
    .roles-cards-sidebar {
      min-height: 420px;
    }
  }
</style>
@endsection

@section('content')
@php
  $rolesPayload = collect($roles)->map(function ($r) {
    $a = is_array($r) ? $r : $r->toArray();
    return [
      'id' => $a['id'],
      'slug' => $a['slug'],
      'name' => $a['name'],
      'description' => $a['description'] ?? '',
      'color' => $a['color'] ?? '#7367f0',
      'icon' => $a['icon'] ?? 'ti tabler-shield',
      'priority' => $a['priority'] ?? 10,
      'is_system' => ! empty($a['is_system']),
      'users_count' => $a['users_count'] ?? 0,
      'permissions' => array_values($a['permissions'] ?? []),
    ];
  })->values();
@endphp

<script type="application/json" id="roles-page-bootstrap">{!! json_encode([
  'roles' => $rolesPayload,
  'flatPermissions' => $flatPermissions,
  'permissions' => $permissions,
], JSON_UNESCAPED_UNICODE) !!}</script>

@php
  $roleCountByCategory = ['all' => count($roles), 'bailiff' => 0, 'management' => 0, 'adventurers' => 0];
  foreach ($roles as $r) {
    $slug = is_array($r) ? ($r['slug'] ?? '') : $r->slug;
    if (in_array($slug, ['ADMIN', 'GM'], true)) {
      $roleCountByCategory['management']++;
    } elseif (str_starts_with($slug, 'BAILIFF')) {
      $roleCountByCategory['bailiff']++;
    } else {
      $roleCountByCategory['adventurers']++;
    }
  }
@endphp

<div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-6 gap-4">
  <div class="d-flex flex-column justify-content-center">
    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
      <h4 class="mb-0">{{ __('Roles & Permissions') }}</h4>
      <span class="badge bg-label-danger">{{ __('Admin') }}</span>
    </div>
    <p class="mb-0 text-body">
      {{ __('Guild roles, Bailiff Council units, and granular route permissions (read / create / update / delete / all).') }}
    </p>
  </div>
  <div class="d-flex align-content-center flex-wrap gap-3">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddRole">
      <i class="icon-base ti tabler-plus me-1"></i> {{ __('Add New Role') }}
    </button>
  </div>
</div>

<div class="card roles-main-card">
  <div class="card-header border-bottom">
    <ul class="nav nav-tabs card-header-tabs mb-0" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-matrix-btn" data-bs-toggle="tab" data-bs-target="#tab-matrix-pane" type="button" role="tab">
          <i class="icon-base ti tabler-table-alias me-1"></i> {{ __('Permission Matrix') }}
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-cards-btn" data-bs-toggle="tab" data-bs-target="#tab-cards-pane" type="button" role="tab">
          <i class="icon-base ti tabler-id-badge-2 me-1"></i> {{ __('Role Cards') }}
          <span class="badge rounded-pill bg-label-primary ms-1">{{ count($roles) }}</span>
        </button>
      </li>
    </ul>
  </div>

  <div class="tab-content">
    <!-- Tab 1: Matrix (default) -->
    <div class="tab-pane fade show active" id="tab-matrix-pane" role="tabpanel">
      <div class="card-body border-bottom">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
          <div>
            <h6 class="mb-1 fw-semibold">{{ __('Permission Matrix (Quick Access & Comparison)') }}</h6>
            <p class="mb-0 text-body-secondary small">{{ __('Toggle switches to grant or revoke permissions per role. Changes sync instantly to role cards.') }}</p>
          </div>
          <div class="d-flex flex-column flex-sm-row align-items-stretch gap-2">
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
              <input type="text" class="form-control" id="matrixSearch" placeholder="{{ __('Search permission...') }}">
            </div>
            <select class="form-select w-100 w-sm-auto" id="matrixModuleFilter" style="min-width: 11rem;">
              <option value="">{{ __('All modules') }}</option>
              @foreach($permissions as $moduleKey => $module)
                <option value="{{ $moduleKey }}">{{ __($module['name']) }}</option>
              @endforeach
            </select>
            <span class="badge bg-label-success d-inline-flex align-items-center justify-content-center px-3">
              <i class="icon-base ti tabler-bolt me-1"></i> {{ __('Live sync') }}
            </span>
          </div>
        </div>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover align-middle mb-0 matrix-table">
          <thead>
            <tr class="table-light">
              <th style="min-width: 270px;">{{ __('System Permission') }}</th>
              @foreach($roles as $r)
                @php
                  $rArr = is_array($r) ? $r : $r->toArray();
                  $isBailiff = str_starts_with($rArr['slug'] ?? '', 'BAILIFF');
                @endphp
                <th class="text-center {{ $isBailiff ? 'bailiff-header-col' : '' }} matrix-role-col" data-role-id="{{ $rArr['id'] }}" style="min-width: 145px;">
                  <span class="d-block fw-bold matrix-role-name" style="color: {{ $rArr['color'] ?? '#7367f0' }}; font-size: 0.88rem;">
                    {{ __($rArr['name']) }}
                  </span>
                  <span class="badge bg-label-secondary font-monospace matrix-role-slug" style="font-size: 0.65rem;">
                    {{ $rArr['slug'] }}
                  </span>
                </th>
              @endforeach
            </tr>
          </thead>
          <tbody id="matrixTableBody">
            @foreach($flatPermissions as $permKey => $p)
              <tr class="matrix-row" data-module="{{ $p['module'] ?? '' }}" data-search="{{ strtolower($permKey . ' ' . ($p['name'] ?? '') . ' ' . ($p['description'] ?? '')) }}">
                <td>
                  <div class="d-flex align-items-center gap-2.5">
                    <span class="avatar avatar-xs">
                      <span class="avatar-initial rounded {{ $p['badge'] ?? 'bg-label-primary' }}">
                        <i class="icon-base {{ $p['icon'] }}" style="font-size: 14px;"></i>
                      </span>
                    </span>
                    <div>
                      <span class="fw-bold text-heading d-block" style="font-size: 0.88rem;">{{ __($p['name']) }}</span>
                      <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;">{{ $permKey }}</small>
                    </div>
                  </div>
                </td>

                @foreach($roles as $r)
                  @php
                    $rArr = is_array($r) ? $r : $r->toArray();
                    $rolePermList = $rArr['permissions'] ?? [];
                    $moduleAll = ($p['module'] ?? '') . '.all';
                    $hasPerm = in_array($permKey, $rolePermList, true)
                        || (($p['action'] ?? '') !== 'all' && in_array($moduleAll, $rolePermList, true));
                    $isAdminRole = ($rArr['slug'] === 'ADMIN');
                    $isLocked = ($isAdminRole && in_array($permKey, ['roles.all', 'roles.manage'], true));
                    $isBailiff = str_starts_with($rArr['slug'] ?? '', 'BAILIFF');
                  @endphp

                  <td class="text-center {{ $isBailiff ? 'bailiff-body-col' : '' }}">
                    <div class="form-check form-switch d-inline-block mb-0">
                      <input class="form-check-input matrix-switch toggle-perm-checkbox"
                             type="checkbox"
                             data-role-id="{{ $rArr['id'] }}"
                             data-perm="{{ $permKey }}"
                             data-module="{{ $p['module'] ?? '' }}"
                             data-action="{{ $p['action'] ?? '' }}"
                             data-role-slug="{{ $rArr['slug'] }}"
                             {{ $hasPerm ? 'checked' : '' }}
                             {{ $isLocked ? 'disabled title="' . __('The Administrator role must retain role management authority.') . '"' : '' }}>
                    </div>
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tab 2: Role cards -->
    <div class="tab-pane fade" id="tab-cards-pane" role="tabpanel">
      <div class="row g-0">
        <div class="col-12 col-lg-3 border-end roles-cards-sidebar">
          <div class="p-4">
            <h6 class="text-body-secondary text-uppercase small fw-semibold mb-3">{{ __('Role groups') }}</h6>
            <div class="list-group role-filter-list" id="roleFilterTabs">
              <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2 btn-role-filter active" data-filter="all">
                <span class="avatar avatar-xs">
                  <span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-cards ti-xs"></i></span>
                </span>
                <span class="flex-grow-1 text-start">{{ __('All Roles') }}</span>
                <span class="badge rounded-pill bg-label-primary">{{ $roleCountByCategory['all'] }}</span>
              </button>
              <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2 btn-role-filter" data-filter="management">
                <span class="avatar avatar-xs">
                  <span class="avatar-initial rounded bg-label-danger"><i class="icon-base ti tabler-crown ti-xs"></i></span>
                </span>
                <span class="flex-grow-1 text-start">{{ __('Guild Leadership') }}</span>
                <span class="badge rounded-pill bg-label-danger">{{ $roleCountByCategory['management'] }}</span>
              </button>
              <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2 btn-role-filter" data-filter="bailiff">
                <span class="avatar avatar-xs">
                  <span class="avatar-initial rounded bg-label-warning"><i class="icon-base ti tabler-shield-check ti-xs"></i></span>
                </span>
                <span class="flex-grow-1 text-start">{{ __('Bailiff Council') }}</span>
                <span class="badge rounded-pill bg-label-warning">{{ $roleCountByCategory['bailiff'] }}</span>
              </button>
              <button type="button" class="list-group-item list-group-item-action d-flex align-items-center gap-2 btn-role-filter" data-filter="adventurers">
                <span class="avatar avatar-xs">
                  <span class="avatar-initial rounded bg-label-info"><i class="icon-base ti tabler-swords ti-xs"></i></span>
                </span>
                <span class="flex-grow-1 text-start">{{ __('Adventurers') }}</span>
                <span class="badge rounded-pill bg-label-info">{{ $roleCountByCategory['adventurers'] }}</span>
              </button>
            </div>
            <div class="mt-4 p-3 rounded border bg-body">
              <div class="d-flex align-items-start gap-2">
                <i class="icon-base ti tabler-info-circle text-primary mt-1"></i>
                <p class="mb-0 small text-body-secondary">{{ __('Hierarchy priority: #100 is highest authority (Administrator). Lower numbers indicate standard adventurer tiers.') }}</p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-9">
          <div class="p-4 border-bottom">
            <div class="input-group input-group-merge">
              <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
              <input type="text" class="form-control" id="cardsRoleSearch" placeholder="{{ __('Search role by name or slug...') }}">
            </div>
          </div>
          <div class="p-4">
            <div class="row g-4" id="rolesCardsGrid">
  @foreach($roles as $r)
    @php
      $rArr = is_array($r) ? $r : $r->toArray();
      $roleColor = $rArr['color'] ?? '#7367f0';
      $roleIcon = $rArr['icon'] ?? 'ti tabler-shield';
      $rolePerms = $rArr['permissions'] ?? [];
      $isSystem = !empty($rArr['is_system']);
      $usersCount = $rArr['users_count'] ?? 0;
      $isBailiff = str_starts_with($rArr['slug'] ?? '', 'BAILIFF');

      $category = 'adventurers';
      if (in_array($rArr['slug'] ?? '', ['ADMIN', 'GM'], true)) {
          $category = 'management';
      } elseif ($isBailiff) {
          $category = 'bailiff';
      }
    @endphp

    <div class="col-12 col-md-6 col-xl-6 role-item-col" id="role-card-{{ $rArr['id'] }}" data-category="{{ $category }}" data-search="{{ strtolower(($rArr['name'] ?? '') . ' ' . ($rArr['slug'] ?? '')) }}">
      <div class="card h-100 role-card shadow-xs {{ $isBailiff ? 'border-warning-subtle' : '' }}">
        <div class="card-body p-4 d-flex flex-column">

          <!-- Top Row: Avatar & Priority Badge -->
          <div class="d-flex align-items-start justify-content-between mb-3">
            <div class="d-flex align-items-center gap-3">
              <span class="role-badge-icon" style="background-color: {{ $roleColor }}20; color: {{ $roleColor }};">
                <i class="icon-base {{ $roleIcon }}"></i>
              </span>
              <div>
                <h5 class="mb-0 fw-bold text-heading">{{ __($rArr['name']) }}</h5>
                <span class="badge font-monospace text-uppercase" style="background-color: {{ $roleColor }}25; color: {{ $roleColor }}; font-size: 0.72rem;">
                  {{ $rArr['slug'] }}
                </span>
                @if($isSystem)
                  <span class="badge bg-label-secondary ms-1" style="font-size: 0.68rem;" title="{{ __('System Role') }}">
                    <i class="icon-base ti tabler-lock-square-rounded me-0.5"></i> {{ __('System') }}
                  </span>
                @endif
              </div>
            </div>
            <div class="text-end">
              <span class="badge bg-label-dark rounded-pill font-monospace" title="{{ __('Hierarchy Priority') }}">
                #{{ $rArr['priority'] ?? 10 }}
              </span>
            </div>
          </div>

          <!-- Description -->
          <p class="text-body-secondary small mb-3" style="min-height: 38px; line-height: 1.5;">
            {{ __($rArr['description'] ?: 'Custom guild role and permission assignment.') }}
          </p>

          <!-- Permissions Summary -->
          <div class="mb-4">
            <span class="fw-semibold small text-body-secondary d-block mb-1.5 role-perm-count" style="font-size: 0.75rem;">
              {{ __('Assigned Permissions:') }} (<span class="role-perm-count-num">{{ count($rolePerms) }}</span>)
            </span>
            <div class="d-flex flex-wrap gap-1 role-perm-badges">
              @forelse($rolePerms as $permKey)
                @php
                  $pInfo = $flatPermissions[$permKey] ?? null;
                  $pName = $pInfo['name'] ?? $permKey;
                  $pBadge = $pInfo['badge'] ?? 'bg-label-primary';
                @endphp
                <span class="badge {{ $pBadge }} rounded-pill" style="font-size: 0.72rem;" title="{{ $permKey }}">
                  {{ __($pName) }}
                </span>
              @empty
                <span class="text-muted small fst-italic" style="font-size: 0.78rem;">
                  <i class="icon-base ti tabler-circle-x me-1"></i> {{ __('Standard adventurer permissions') }}
                </span>
              @endforelse
            </div>
          </div>

          <!-- Bottom Row: Users & Actions -->
          <div class="d-flex align-items-center justify-content-between pt-3 mt-auto border-top">
            <span class="small fw-semibold text-body-secondary">
              <i class="icon-base ti tabler-users me-1 text-primary"></i>
              {{ $usersCount }} {{ __('Adventurers') }}
            </span>

            <div class="d-flex align-items-center gap-1">
              <!-- Edit Button -->
              <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 btn-edit-role"
                      data-id="{{ $rArr['id'] }}"
                      data-slug="{{ $rArr['slug'] }}"
                      data-name="{{ $rArr['name'] }}"
                      data-description="{{ $rArr['description'] ?? '' }}"
                      data-color="{{ $roleColor }}"
                      data-icon="{{ $roleIcon }}"
                      data-priority="{{ $rArr['priority'] ?? 10 }}"
                      data-permissions="{{ json_encode($rolePerms) }}"
                      data-is-system="{{ $isSystem ? '1' : '0' }}">
                <i class="icon-base ti tabler-pencil me-1"></i> {{ __('Configure Permissions') }}
              </button>

              @if(!$isSystem)
                <!-- Delete Button -->
                <button type="button" class="btn btn-sm btn-icon btn-outline-danger rounded-circle btn-delete-role"
                        data-id="{{ $rArr['id'] }}"
                        data-name="{{ $rArr['name'] }}"
                        data-users="{{ $usersCount }}"
                        title="{{ __('Delete Role') }}">
                  <i class="icon-base ti tabler-trash"></i>
                </button>
              @endif
            </div>
          </div>

        </div>
      </div>
    </div>
  @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ================= MODALS ================= -->

<!-- 1. Modal: Edit Role & Permissions -->
<div class="modal fade" id="modalEditRole" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow-lg">
      <div class="modal-header border-bottom py-3">
        <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2 mb-0">
          <i class="icon-base ti tabler-pencil text-primary fs-4"></i>
          <span>{{ __('Configure Role & Permissions') }}</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <form id="formEditRole">
        @csrf
        <input type="hidden" id="editRoleId">
        <input type="hidden" id="editRoleSlug">

        <div class="modal-body p-4">
          <!-- Row 1: Name, Color, Priority -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-5">
              <label class="form-label fw-semibold" for="editRoleName">{{ __('Role Name') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="editRoleName" required placeholder="e.g. Senior Bailiff">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label fw-semibold" for="editRoleColor">{{ __('Role Color') }}</label>
              <div class="input-group">
                <input type="color" class="form-control form-control-color" id="editRoleColor" value="#7367f0" title="{{ __('Select Color') }}">
                <input type="text" class="form-control font-monospace" id="editRoleColorHex" value="#7367f0">
              </div>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label fw-semibold" for="editRolePriority">
                {{ __('Hierarchy Priority') }}
                <span class="badge bg-label-info ms-1" style="font-size: 0.65rem;">0-100</span>
              </label>
              <input type="number" class="form-control" id="editRolePriority" min="0" max="100" value="50">
            </div>
          </div>

          <!-- Row 2: Description & Icon -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-8">
              <label class="form-label fw-semibold" for="editRoleDescription">{{ __('Description / Role Objective') }}</label>
              <input type="text" class="form-control" id="editRoleDescription" placeholder="{{ __('Purpose and duties of this role in the guild...') }}">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold" for="editRoleIcon">{{ __('Role Icon') }}</label>
              <select class="form-select" id="editRoleIcon">
                <option value="ti tabler-gavel">⚖️ Gavel (Discipline Bailiff)</option>
                <option value="ti tabler-users-group">👥 Users Group (Hero Resources)</option>
                <option value="ti tabler-flag">🚩 Flag (Guild Representative Bailiff)</option>
                <option value="ti tabler-books">📚 Books (Archivist Bailiff)</option>
                <option value="ti tabler-shield-lock">🔒 Shield Lock (Oathkeeper Bailiff)</option>
                <option value="ti tabler-shield-check">🛡️ Shield Check (Bailiff & Guard)</option>
                <option value="ti tabler-package">📦 Package (Inventory Bailiff)</option>
                <option value="ti tabler-clipboard-list">📜 Scroll (Questmaster Bailiff)</option>
                <option value="ti tabler-crown">👑 Crown (Administrator)</option>
                <option value="ti tabler-wand">🪄 Wand (Game Master)</option>
                <option value="ti tabler-swords">⚔️ Swords (Veteran Adventurer)</option>
                <option value="ti tabler-medal">🎖️ Medal (Member)</option>
                <option value="ti tabler-sparkles">✨ Sparkles (Special Role)</option>
                <option value="ti tabler-user">👤 User (Adventurer)</option>
              </select>
            </div>
          </div>

          <!-- Section: Permissions Selection -->
          <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
            <div>
              <h6 class="fw-bold mb-0 text-heading d-flex align-items-center gap-1.5">
                <i class="icon-base ti tabler-key text-warning"></i>
                <span>{{ __('System Permissions') }}</span>
              </h6>
              <small class="text-body-secondary">{{ __('Check permissions to grant to this role.') }}</small>
            </div>
            <div class="d-flex gap-1">
              <button type="button" class="btn btn-xs btn-outline-primary rounded-pill" id="btnSelectAllPerms">{{ __('Select All') }}</button>
              <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill" id="btnDeselectAllPerms">{{ __('Deselect All') }}</button>
            </div>
          </div>

          <div class="row g-3">
            @foreach($permissions as $moduleKey => $module)
              <div class="col-12 col-md-6">
                <div class="card border p-3 rounded-3 h-100 bg-body">
                  <div class="d-flex align-items-center gap-1.5 mb-2">
                    <span class="badge {{ $module['badge'] ?? 'bg-label-primary' }} rounded-pill" style="font-size: 0.7rem;">
                      <i class="icon-base {{ $module['icon'] }} me-0.5" style="font-size: 11px;"></i> {{ __($module['name']) }}
                    </span>
                  </div>
                  <small class="text-body-secondary d-block mb-2" style="font-size: 0.75rem;">{{ __($module['description']) }}</small>
                  <div class="d-flex flex-wrap gap-2">
                    @foreach($module['actions'] as $action)
                      <div class="form-check form-switch mb-0">
                        <input class="form-check-input edit-perm-checkbox module-{{ $moduleKey }}-all-toggle" type="checkbox" id="edit_perm_{{ str_replace('.', '_', $action['key']) }}" value="{{ $action['key'] }}" data-module="{{ $moduleKey }}">
                        <label class="form-check-label small" for="edit_perm_{{ str_replace('.', '_', $action['key']) }}">{{ $action['label'] }}</label>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
            @endforeach
          </div>

        </div>
        <div class="modal-footer border-top d-flex justify-content-between">
          <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4" id="btnSubmitEditRole">
            <i class="icon-base ti tabler-check me-1"></i> {{ __('Save Changes') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 2. Modal: Add New Role -->
<div class="modal fade" id="modalAddRole" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content shadow-lg border-primary">
      <div class="modal-header border-bottom py-3">
        <h5 class="modal-title fw-bold text-heading d-flex align-items-center gap-2 mb-0">
          <i class="icon-base ti tabler-shield-plus text-primary fs-4"></i>
          <span>{{ __('Create New Guild Role') }}</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <form id="formAddRole">
        @csrf
        <div class="modal-body p-4">

          <!-- Quick Preset Template Selector -->
          <div class="mb-4 p-3 rounded-3" style="background: rgba(255, 159, 67, 0.08); border: 1px dashed rgba(255, 159, 67, 0.4);">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <label class="form-label fw-bold text-heading mb-0 small d-flex align-items-center gap-1.5" for="selectRoleTemplate">
                <i class="icon-base ti tabler-template text-warning"></i>
                <span>{{ __('Quick Role / Bailiff Template') }}</span>
              </label>
              <span class="badge bg-label-warning font-monospace" style="font-size: 0.68rem;">{{ __('Auto-fill Form') }}</span>
            </div>
            <select class="form-select form-select-sm" id="selectRoleTemplate">
              <option value="">-- {{ __('Select a Template (Optional)') }} --</option>
              <option value="bailiff_discipline">⚖️ {{ __('Discipline Bailiff (Discipline & Conduct, Priority 65)') }}</option>
              <option value="bailiff_hr">👥 {{ __('Hero Resources Bailiff (Intake, Promotion & XP, Priority 65)') }}</option>
              <option value="bailiff_representative">🚩 {{ __('Guild Representative Bailiff (Diplomacy & External Affairs, Priority 65)') }}</option>
              <option value="bailiff_archivist">📚 {{ __('Archivist Bailiff (Chronicles, Records & Library, Priority 65)') }}</option>
              <option value="bailiff_oathkeeper">🔒 {{ __('Oathkeeper Bailiff (Sacred Oaths & Covenants, Priority 65)') }}</option>
              <option value="bailiff_inventory">📦 {{ __('Inventory Bailiff (Vault & Storage, Priority 60)') }}</option>
              <option value="bailiff_questmaster">📜 {{ __('Questmaster Bailiff (Quest Board & Decrees, Priority 60)') }}</option>
              <option value="bailiff_custom">🛡️ {{ __('Custom Bailiff Role (Priority 60)') }}</option>
              <option value="veteran">⚔️ {{ __('Veteran Adventurer (Priority 40)') }}</option>
            </select>
          </div>

          <!-- Row 1: Name, Color, Priority -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-5">
              <label class="form-label fw-semibold" for="addRoleName">{{ __('Role Name') }} <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="addRoleName" required placeholder="e.g. Discipline Bailiff">
            </div>
            <div class="col-6 col-md-3">
              <label class="form-label fw-semibold" for="addRoleColor">{{ __('Role Color') }}</label>
              <div class="input-group">
                <input type="color" class="form-control form-control-color" id="addRoleColor" value="#00cfe8" title="{{ __('Select Color') }}">
                <input type="text" class="form-control font-monospace" id="addRoleColorHex" value="#00cfe8">
              </div>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label fw-semibold" for="addRolePriority">
                {{ __('Hierarchy Priority') }}
                <span class="badge bg-label-info ms-1" style="font-size: 0.65rem;">0-100</span>
              </label>
              <input type="number" class="form-control" id="addRolePriority" min="0" max="100" value="30">
            </div>
          </div>

          <!-- Row 2: Description & Icon -->
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-8">
              <label class="form-label fw-semibold" for="addRoleDescription">{{ __('Description / Role Objective') }}</label>
              <input type="text" class="form-control" id="addRoleDescription" placeholder="{{ __('Purpose and duties of this role in the guild...') }}">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-semibold" for="addRoleIcon">{{ __('Role Icon') }}</label>
              <select class="form-select" id="addRoleIcon">
                <option value="ti tabler-gavel">⚖️ Gavel (Discipline Bailiff)</option>
                <option value="ti tabler-users-group">👥 Users Group (Hero Resources)</option>
                <option value="ti tabler-flag">🚩 Flag (Guild Representative Bailiff)</option>
                <option value="ti tabler-books">📚 Books (Archivist Bailiff)</option>
                <option value="ti tabler-shield-lock">🔒 Shield Lock (Oathkeeper Bailiff)</option>
                <option value="ti tabler-shield-check">🛡️ Shield Check (Bailiff & Guard)</option>
                <option value="ti tabler-package">📦 Package (Inventory Bailiff)</option>
                <option value="ti tabler-clipboard-list">📜 Scroll (Questmaster Bailiff)</option>
                <option value="ti tabler-crown">👑 Crown (Administrator)</option>
                <option value="ti tabler-wand">🪄 Wand (Game Master)</option>
                <option value="ti tabler-swords">⚔️ Swords (Veteran Adventurer)</option>
                <option value="ti tabler-medal">🎖️ Medal (Member)</option>
                <option value="ti tabler-sparkles">✨ Sparkles (Special Role)</option>
                <option value="ti tabler-user">👤 User (Adventurer)</option>
              </select>
            </div>
          </div>

          <!-- Section: Permissions Selection -->
          <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
            <div>
              <h6 class="fw-bold mb-0 text-heading d-flex align-items-center gap-1.5">
                <i class="icon-base ti tabler-shield-check text-success"></i>
                <span>{{ __('Initial Permissions') }}</span>
              </h6>
              <small class="text-body-secondary">{{ __('Check permissions to grant initially to this role.') }}</small>
            </div>
          </div>

          <div class="row g-3">
            @foreach($permissions as $moduleKey => $module)
              <div class="col-12 col-md-6">
                <div class="card border p-3 rounded-3 h-100 bg-body">
                  <div class="d-flex align-items-center gap-1.5 mb-2">
                    <span class="badge {{ $module['badge'] ?? 'bg-label-primary' }} rounded-pill" style="font-size: 0.7rem;">
                      <i class="icon-base {{ $module['icon'] }} me-0.5" style="font-size: 11px;"></i> {{ __($module['name']) }}
                    </span>
                  </div>
                  <small class="text-body-secondary d-block mb-2" style="font-size: 0.75rem;">{{ __($module['description']) }}</small>
                  <div class="d-flex flex-wrap gap-2">
                    @foreach($module['actions'] as $action)
                      <div class="form-check form-switch mb-0">
                        <input class="form-check-input add-perm-checkbox" type="checkbox" id="add_perm_{{ str_replace('.', '_', $action['key']) }}" value="{{ $action['key'] }}">
                        <label class="form-check-label small" for="add_perm_{{ str_replace('.', '_', $action['key']) }}">{{ $action['label'] }}</label>
                      </div>
                    @endforeach
                  </div>
                </div>
              </div>
            @endforeach
          </div>

        </div>
        <div class="modal-footer border-top d-flex justify-content-between">
          <button type="button" class="btn btn-label-secondary rounded-pill" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
          <button type="submit" class="btn btn-primary rounded-pill fw-bold px-4" id="btnSubmitAddRole">
            <i class="icon-base ti tabler-plus me-1"></i> {{ __('Create Role') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {

  const bootstrapEl = document.getElementById('roles-page-bootstrap');
  const bootstrapData = bootstrapEl ? JSON.parse(bootstrapEl.textContent || '{}') : {};
  const flatPermissions = bootstrapData.flatPermissions || {};
  const rolesState = {};
  (bootstrapData.roles || []).forEach(r => { rolesState[String(r.id)] = { ...r, permissions: [...(r.permissions || [])] }; });

  function escapeHtml(str) {
    return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function roleHasPermission(permissions, permKey) {
    if (!Array.isArray(permissions)) return false;
    if (permissions.includes(permKey)) return true;
    const meta = flatPermissions[permKey];
    if (!meta) return false;
    const moduleAll = `${meta.module}.all`;
    if (meta.action !== 'all' && permissions.includes(moduleAll)) return true;
    return false;
  }

  function syncMatrixColumn(roleId, permissions) {
    document.querySelectorAll(`.toggle-perm-checkbox[data-role-id="${roleId}"]`).forEach(sw => {
      sw.checked = roleHasPermission(permissions, sw.dataset.perm);
    });
  }

  function renderRoleCardPermissions(role) {
    const card = document.getElementById('role-card-' + role.id);
    if (!card) return;
    const badgesWrap = card.querySelector('.role-perm-badges');
    const countNum = card.querySelector('.role-perm-count-num');
    const perms = role.permissions || [];
    if (countNum) countNum.textContent = String(perms.length);
    if (!badgesWrap) return;
    if (!perms.length) {
      badgesWrap.innerHTML = `<span class="text-muted small fst-italic" style="font-size: 0.78rem;"><i class="icon-base ti tabler-circle-x me-1"></i> {{ __('Standard adventurer permissions') }}</span>`;
      return;
    }
    badgesWrap.innerHTML = perms.map(permKey => {
      const pInfo = flatPermissions[permKey] || {};
      const pName = pInfo.name || permKey;
      const pBadge = pInfo.badge || 'bg-label-primary';
      return `<span class="badge ${pBadge} rounded-pill" style="font-size: 0.72rem;" title="${escapeHtml(permKey)}">${escapeHtml(pName)}</span>`;
    }).join('');
    const editBtn = card.querySelector('.btn-edit-role');
    if (editBtn) {
      editBtn.dataset.permissions = JSON.stringify(perms);
      editBtn.dataset.name = role.name;
      editBtn.dataset.description = role.description || '';
      editBtn.dataset.color = role.color || '#7367f0';
      editBtn.dataset.icon = role.icon || 'ti tabler-shield';
      editBtn.dataset.priority = String(role.priority ?? 10);
    }
  }

  function applyRoleUpdate(role) {
    if (!role || role.id == null) return;
    const id = String(role.id);
    rolesState[id] = { ...(rolesState[id] || {}), ...role, permissions: [...(role.permissions || [])] };
    renderRoleCardPermissions(rolesState[id]);
    syncMatrixColumn(id, rolesState[id].permissions);
  }

  const tabMatrixBtn = document.getElementById('tab-matrix-btn');
  const tabCardsBtn = document.getElementById('tab-cards-btn');
  tabMatrixBtn?.addEventListener('shown.bs.tab', () => sessionStorage.setItem('rolesActiveTab', 'matrix'));
  tabCardsBtn?.addEventListener('shown.bs.tab', () => sessionStorage.setItem('rolesActiveTab', 'cards'));
  if (sessionStorage.getItem('rolesActiveTab') === 'cards' && tabCardsBtn) {
    bootstrap.Tab.getOrCreateInstance(tabCardsBtn).show();
  }

  let activeRoleCategory = 'all';
  const cardsRoleSearch = document.getElementById('cardsRoleSearch');
  function applyRoleCardsFilter() {
    const q = (cardsRoleSearch?.value || '').trim().toLowerCase();
    document.querySelectorAll('.role-item-col').forEach(cardCol => {
      const cat = cardCol.dataset.category;
      const matchCategory = activeRoleCategory === 'all' || cat === activeRoleCategory;
      const matchSearch = !q || (cardCol.dataset.search || '').includes(q);
      cardCol.classList.toggle('role-card-hidden', !(matchCategory && matchSearch));
    });
  }
  cardsRoleSearch?.addEventListener('input', applyRoleCardsFilter);

  // Matrix filters
  const matrixSearch = document.getElementById('matrixSearch');
  const matrixModuleFilter = document.getElementById('matrixModuleFilter');
  function filterMatrixRows() {
    const q = (matrixSearch?.value || '').trim().toLowerCase();
    const mod = matrixModuleFilter?.value || '';
    document.querySelectorAll('#matrixTableBody .matrix-row').forEach(row => {
      const matchModule = !mod || row.dataset.module === mod;
      const matchSearch = !q || (row.dataset.search || '').includes(q);
      row.classList.toggle('matrix-row-hidden', !(matchModule && matchSearch));
    });
  }
  matrixSearch?.addEventListener('input', filterMatrixRows);
  matrixModuleFilter?.addEventListener('change', filterMatrixRows);

  // Color input sync for Edit Modal
  const editColorInput = document.getElementById('editRoleColor');
  const editColorHex = document.getElementById('editRoleColorHex');
  if (editColorInput && editColorHex) {
    editColorInput.addEventListener('input', function() {
      editColorHex.value = this.value;
    });
    editColorHex.addEventListener('input', function() {
      if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
        editColorInput.value = this.value;
      }
    });
  }

  // Color input sync for Add Modal
  const addColorInput = document.getElementById('addRoleColor');
  const addColorHex = document.getElementById('addRoleColorHex');
  if (addColorInput && addColorHex) {
    addColorInput.addEventListener('input', function() {
      addColorHex.value = this.value;
    });
    addColorHex.addEventListener('input', function() {
      if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
        addColorInput.value = this.value;
      }
    });
  }

  document.querySelectorAll('.btn-role-filter').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.btn-role-filter').forEach(b => b.classList.remove('active'));
      this.classList.add('active');
      activeRoleCategory = this.dataset.filter || 'all';
      applyRoleCardsFilter();
    });
  });

  // Quick Template Preset Selector in Add Modal
  const templateSelect = document.getElementById('selectRoleTemplate');
  if (templateSelect) {
    templateSelect.addEventListener('change', function() {
      const val = this.value;
      if (!val) return;

      const templates = {
        bailiff_discipline: {
          name: 'Discipline Bailiff',
          color: '#e65100',
          priority: 65,
          desc: 'Oversees guild discipline, rule compliance, sanctions, and member conduct.',
          icon: 'ti tabler-gavel',
          perms: ['discipline.all', 'users.all', 'guild.read']
        },
        bailiff_hr: {
          name: 'Hero Resources Bailiff',
          color: '#0288d1',
          priority: 65,
          desc: 'Manages adventurer intake, rank advancement, roster assignments, and progression.',
          icon: 'ti tabler-users-group',
          perms: ['hr.all', 'xp.all', 'users.all', 'guild.read']
        },
        bailiff_representative: {
          name: 'Guild Representative Bailiff',
          color: '#10b981',
          priority: 65,
          desc: 'Official guild ambassador managing diplomatic relations, external delegations, and alliance treaties.',
          icon: 'ti tabler-flag',
          perms: ['diplomacy.all', 'guild.read']
        },
        bailiff_archivist: {
          name: 'Archivist Bailiff',
          color: '#8b5cf6',
          priority: 65,
          desc: 'Guardian of the Great Library, guild annals, quest archives, and ancient historical scrolls.',
          icon: 'ti tabler-books',
          perms: ['archives.all', 'guild.read']
        },
        bailiff_oathkeeper: {
          name: 'Oathkeeper Bailiff',
          color: '#06b6d4',
          priority: 65,
          desc: 'Keeper of blood bonds, initiation vows, adventurer pledges, and sacred covenants.',
          icon: 'ti tabler-shield-lock',
          perms: ['oaths.all', 'discipline.all', 'guild.read']
        },
        bailiff_inventory: {
          name: 'Inventory Bailiff',
          color: '#f57c00',
          priority: 60,
          desc: 'Manages the guild communal vault, magic artifacts, and treasury assets.',
          icon: 'ti tabler-package',
          perms: ['inventory.all', 'guild.read']
        },
        bailiff_questmaster: {
          name: 'Questmaster Bailiff',
          color: '#7b1fa2',
          priority: 60,
          desc: 'Supervises quest board notices, decrees, and expedition debriefs.',
          icon: 'ti tabler-clipboard-list',
          perms: ['quests.all', 'guild.read']
        },
        bailiff_custom: {
          name: 'Custom Bailiff',
          color: '#ff9f43',
          priority: 60,
          desc: 'Special bailiff council division officer.',
          icon: 'ti tabler-shield-check',
          perms: ['guild.read']
        },
        veteran: {
          name: 'Veteran Adventurer',
          color: '#00cfe8',
          priority: 40,
          desc: 'Hardened veterans who survived high-stakes guild expeditions.',
          icon: 'ti tabler-swords',
          perms: []
        }
      };

      const t = templates[val];
      if (!t) return;

      document.getElementById('addRoleName').value = t.name;
      document.getElementById('addRoleColor').value = t.color;
      document.getElementById('addRoleColorHex').value = t.color;
      document.getElementById('addRolePriority').value = t.priority;
      document.getElementById('addRoleDescription').value = t.desc;
      document.getElementById('addRoleIcon').value = t.icon;

      document.querySelectorAll('.add-perm-checkbox').forEach(cb => {
        cb.checked = t.perms.includes(cb.value);
      });
    });
  }

  // Edit Role Modal Trigger
  document.querySelectorAll('.btn-edit-role').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      const slug = this.dataset.slug;
      const name = this.dataset.name;
      const description = this.dataset.description;
      const color = this.dataset.color || '#7367f0';
      const icon = this.dataset.icon || 'ti tabler-shield';
      const priority = this.dataset.priority || '50';
      const perms = JSON.parse(this.dataset.permissions || '[]');
      const isSystem = this.dataset.isSystem === '1';

      document.getElementById('editRoleId').value = id;
      document.getElementById('editRoleSlug').value = slug;
      document.getElementById('editRoleName').value = name;
      document.getElementById('editRoleDescription').value = description;
      document.getElementById('editRoleColor').value = color;
      document.getElementById('editRoleColorHex').value = color;
      document.getElementById('editRolePriority').value = priority;
      document.getElementById('editRoleIcon').value = icon;

      // Reset checkboxes (respect module.all implied grants)
      document.querySelectorAll('.edit-perm-checkbox').forEach(cb => {
        cb.checked = roleHasPermission(perms, cb.value);
        cb.disabled = false;
      });

      // Guard Admin from unchecking roles.manage
      if (slug === 'ADMIN') {
        const adminPerm = document.getElementById('edit_perm_roles_all');
        if (adminPerm) {
          adminPerm.checked = true;
          adminPerm.disabled = true;
        }
      }

      const modal = new bootstrap.Modal(document.getElementById('modalEditRole'));
      modal.show();
    });
  });

  // Select All & Deselect All in Edit Modal
  const btnSelectAll = document.getElementById('btnSelectAllPerms');
  const btnDeselectAll = document.getElementById('btnDeselectAllPerms');
  if (btnSelectAll) {
    btnSelectAll.addEventListener('click', function() {
      document.querySelectorAll('.edit-perm-checkbox').forEach(cb => cb.checked = true);
    });
  }
  if (btnDeselectAll) {
    btnDeselectAll.addEventListener('click', function() {
      const slug = document.getElementById('editRoleSlug').value;
      document.querySelectorAll('.edit-perm-checkbox').forEach(cb => {
        if (slug === 'ADMIN' && (cb.value === 'roles.all' || cb.value === 'roles.manage')) {
          cb.checked = true;
        } else {
          cb.checked = false;
        }
      });
    });
  }

  // Submit Edit Role Form
  const formEdit = document.getElementById('formEditRole');
  if (formEdit) {
    formEdit.addEventListener('submit', function(e) {
      e.preventDefault();
      const id = document.getElementById('editRoleId').value;
      const btn = document.getElementById('btnSubmitEditRole');
      btn.disabled = true;

      const perms = [];
      document.querySelectorAll('.edit-perm-checkbox:checked').forEach(cb => {
        perms.push(cb.value);
      });

      const payload = {
        name: document.getElementById('editRoleName').value.trim(),
        description: document.getElementById('editRoleDescription').value.trim(),
        color: document.getElementById('editRoleColorHex').value.trim(),
        icon: document.getElementById('editRoleIcon').value,
        priority: parseInt(document.getElementById('editRolePriority').value) || 10,
        permissions: perms
      };

      fetch("{{ url('/valiant-shade/app/roles') }}/" + id, {
        method: 'PUT',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        if (data.success) {
          applyRoleUpdate(data.role);
          bootstrap.Modal.getInstance(document.getElementById('modalEditRole'))?.hide();
          Swal.fire({
            icon: 'success',
            title: '{{ __("Success!") }}',
            text: data.message,
            timer: 1400,
            showConfirmButton: false
          });
        } else {
          Swal.fire({ icon: 'error', title: '{{ __("Error!") }}', text: data.message });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: '{{ __("Error!") }}', text: '{{ __("Cannot reach server.") }}' });
      });
    });
  }

  // Submit Add Role Form
  const formAdd = document.getElementById('formAddRole');
  if (formAdd) {
    formAdd.addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = document.getElementById('btnSubmitAddRole');
      btn.disabled = true;

      const perms = [];
      document.querySelectorAll('.add-perm-checkbox:checked').forEach(cb => {
        perms.push(cb.value);
      });

      const payload = {
        name: document.getElementById('addRoleName').value.trim(),
        description: document.getElementById('addRoleDescription').value.trim(),
        color: document.getElementById('addRoleColorHex').value.trim(),
        icon: document.getElementById('addRoleIcon').value,
        priority: parseInt(document.getElementById('addRolePriority').value) || 10,
        permissions: perms
      };

      fetch("{{ route('valiant-shade.app-roles.store') }}", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: '{{ __("Role Created!") }}',
            text: data.message,
            timer: 1600,
            showConfirmButton: false
          }).then(() => location.reload());
        } else {
          Swal.fire({ icon: 'error', title: '{{ __("Error!") }}', text: data.message });
        }
      })
      .catch(() => {
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: '{{ __("Error!") }}', text: '{{ __("Cannot reach server.") }}' });
      });
    });
  }

  // Delete Role Trigger
  document.querySelectorAll('.btn-delete-role').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.dataset.id;
      const name = this.dataset.name;
      const users = parseInt(this.dataset.users) || 0;

      if (users > 0) {
        Swal.fire({
          icon: 'warning',
          title: '{{ __("Cannot Delete Role") }}',
          text: '{{ __("This role is assigned to :count adventurers. Reassign them first.") }}'.replace(':count', users)
        });
        return;
      }

      Swal.fire({
        title: '{{ __("Are you sure you want to delete this role?") }}',
        text: '"' + name + '" {{ __("will be permanently deleted.") }}',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '{{ __("Yes, Delete!") }}',
        cancelButtonText: '{{ __("Cancel") }}'
      }).then(result => {
        if (result.isConfirmed) {
          fetch("{{ url('/valiant-shade/app/roles') }}/" + id, {
            method: 'DELETE',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json'
            }
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              Swal.fire({
                icon: 'success',
                title: '{{ __("Deleted!") }}',
                text: data.message,
                timer: 1400,
                showConfirmButton: false
              }).then(() => location.reload());
            } else {
              Swal.fire({ icon: 'error', title: '{{ __("Error!") }}', text: data.message });
            }
          })
          .catch(() => {
            Swal.fire({ icon: 'error', title: '{{ __("Error!") }}', text: '{{ __("Cannot reach server.") }}' });
          });
        }
      });
    });
  });

  // Interactive Permission Matrix Quick Toggle Switch (delegated — survives filters)
  document.getElementById('matrixTableBody')?.addEventListener('change', function(e) {
    const sw = e.target.closest('.toggle-perm-checkbox');
    if (!sw) return;

    const roleId = sw.dataset.roleId;
    const perm = sw.dataset.perm;
    const enabled = sw.checked;
    const roleSlug = sw.dataset.roleSlug;
    const previousPerms = [...(rolesState[roleId]?.permissions || [])];

    if (roleSlug === 'ADMIN' && (perm === 'roles.manage' || perm === 'roles.all') && !enabled) {
      sw.checked = true;
      Swal.fire({
        icon: 'warning',
        title: '{{ __("Action Blocked") }}',
        text: '{{ __("The Administrator role must retain role management authority.") }}'
      });
      return;
    }

    sw.disabled = true;

    fetch("{{ url('/valiant-shade/app/roles') }}/" + roleId + "/toggle-permission", {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ permission: perm, enabled: enabled })
    })
    .then(res => res.json())
    .then(data => {
      sw.disabled = false;
      if (data.success && data.role) {
        applyRoleUpdate(data.role);
        const toast = Swal.mixin({
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 1800,
          timerProgressBar: true
        });
        toast.fire({ icon: 'success', title: data.message });
      } else {
        sw.checked = !enabled;
        rolesState[roleId] = { ...rolesState[roleId], permissions: previousPerms };
        syncMatrixColumn(roleId, previousPerms);
        Swal.fire({ icon: 'error', title: '{{ __("Error!") }}', text: data.message || '{{ __("Update failed.") }}' });
      }
    })
    .catch(() => {
      sw.disabled = false;
      sw.checked = !enabled;
      rolesState[roleId] = { ...rolesState[roleId], permissions: previousPerms };
      syncMatrixColumn(roleId, previousPerms);
      Swal.fire({ icon: 'error', title: '{{ __("Error!") }}', text: '{{ __("Cannot reach server.") }}' });
    });
  });

});
</script>
@endsection
