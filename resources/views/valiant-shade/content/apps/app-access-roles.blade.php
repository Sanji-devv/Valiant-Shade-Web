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
  .btn-role-filter.active {
    background-color: var(--bs-primary) !important;
    color: #fff !important;
  }
</style>
@endsection

@section('content')
<!-- Header Banner -->
<div class="card mb-6 border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(234, 84, 85, 0.12) 0%, rgba(255, 159, 67, 0.12) 50%, rgba(115, 103, 240, 0.15) 100%);">
  <div class="card-body p-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-lg">
          <span class="avatar-initial rounded-3 bg-danger text-white shadow-sm fs-2">
            <i class="icon-base ti tabler-shield-lock"></i>
          </span>
        </div>
        <div>
          <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <h4 class="mb-0 fw-bold text-heading">{{ __('Roles & Permissions Management') }}</h4>
            <span class="badge bg-label-danger font-monospace text-uppercase" style="font-size: 0.72rem;">
              <i class="icon-base ti tabler-lock me-1"></i> {{ __('Admin Only') }}
            </span>
            <span class="badge bg-label-warning font-monospace text-uppercase" style="font-size: 0.72rem;">
              <i class="icon-base ti tabler-shield-check me-1"></i> {{ __('Bailiff Council Sub-tiers Active') }}
            </span>
          </div>
          <p class="mb-0 text-body-secondary small">
            {{ __('Manage all guild roles, Bailiff Council divisions (Discipline, Hero Resources, Inventory, Questmaster, Representative, Archivist, Oathkeeper), hierarchy priorities, and system permissions.') }}
          </p>
        </div>
      </div>
      <div>
        <button type="button" class="btn btn-primary rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddRole">
          <i class="icon-base ti tabler-plus me-1"></i> {{ __('Add New Role') }}
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Section 1: Role Filter Tabs & Role Cards Grid -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <div class="nav nav-pills gap-1 p-1 rounded bg-body shadow-xs" id="roleFilterTabs" role="tablist">
    <button class="nav-link active rounded-pill px-3 py-1.5 btn-role-filter" data-filter="all">
      <i class="icon-base ti tabler-cards me-1"></i> {{ __('All Roles') }} ({{ count($roles) }})
    </button>
    <button class="nav-link rounded-pill px-3 py-1.5 btn-role-filter" data-filter="bailiff">
      <i class="icon-base ti tabler-shield-check me-1 text-warning"></i> {{ __('Bailiff Council & Sub-tiers') }}
    </button>
    <button class="nav-link rounded-pill px-3 py-1.5 btn-role-filter" data-filter="management">
      <i class="icon-base ti tabler-crown me-1 text-danger"></i> {{ __('Guild Leadership') }}
    </button>
    <button class="nav-link rounded-pill px-3 py-1.5 btn-role-filter" data-filter="adventurers">
      <i class="icon-base ti tabler-swords me-1 text-info"></i> {{ __('Adventurers') }}
    </button>
  </div>
  <small class="text-body-secondary">
    {{ __('Hierarchy priority (#100 is highest authority).') }}
  </small>
</div>

<div class="row g-4 mb-6" id="rolesCardsGrid">
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

    <div class="col-12 col-md-6 col-xl-4 role-item-col" id="role-card-{{ $rArr['id'] }}" data-category="{{ $category }}">
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

          <!-- Bailiff Sub-tier Tag if applicable -->
          @if($isBailiff)
            <div class="mb-3">
              <span class="badge rounded-pill d-inline-flex align-items-center gap-1.5 shadow-xs px-2.5 py-1.5"
                    style="background-color: {{ $roleColor }}18; color: {{ $roleColor }}; border: 1px solid {{ $roleColor }}40; font-size: 0.73rem;">
                <i class="icon-base ti tabler-shield-check"></i>
                <span>{{ __('Bailiff Sub-tier:') }} <strong>{{ __($bailiffTier ?: 'Bailiff Council') }}</strong></span>
              </span>
            </div>
          @endif

          <!-- Description -->
          <p class="text-body-secondary small mb-3" style="min-height: 38px; line-height: 1.5;">
            {{ __($rArr['description'] ?: 'Custom guild role and permission assignment.') }}
          </p>

          <!-- Permissions Summary -->
          <div class="mb-4">
            <span class="fw-semibold small text-body-secondary d-block mb-1.5" style="font-size: 0.75rem;">
              {{ __('Assigned Permissions:') }} ({{ count($rolePerms) }})
            </span>
            <div class="d-flex flex-wrap gap-1">
              @forelse($rolePerms as $permKey)
                @php
                  $pInfo = $permissions[$permKey] ?? null;
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
                      data-is-system="{{ $isSystem ? '1' : '0' }}"
                      data-is-bailiff="{{ $isBailiff ? '1' : '0' }}"
                      data-bailiff-tier="{{ $bailiffTier ?? '' }}">
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

<!-- Section 2: Permission Matrix Table (Interactive Permission Grid) -->
<div class="card shadow-sm border-0 mb-6">
  <div class="card-header border-bottom py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <div>
      <h5 class="mb-0 fw-bold text-heading d-flex align-items-center gap-2">
        <i class="icon-base ti tabler-table-alias text-warning"></i>
        <span>{{ __('Permission Matrix (Quick Access & Comparison)') }}</span>
      </h5>
      <small class="text-body-secondary">{{ __('Toggle switches to instantly grant or revoke authority for any role.') }}</small>
    </div>
    <span class="badge bg-label-info font-monospace" style="font-size: 0.75rem;">
      <i class="icon-base ti tabler-bolt me-1"></i> {{ __('Auto-Sync (Instant Save)') }}
    </span>
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
            <th class="text-center {{ $isBailiff ? 'bailiff-header-col' : '' }}" style="min-width: 145px;">
              <span class="d-block fw-bold" style="color: {{ $rArr['color'] ?? '#7367f0' }}; font-size: 0.88rem;">
                {{ __($rArr['name']) }}
              </span>
              <span class="badge bg-label-secondary font-monospace" style="font-size: 0.65rem;">
                {{ $rArr['slug'] }}
              </span>
            </th>
          @endforeach
        </tr>
      </thead>
      <tbody>
        @foreach($permissions as $permKey => $p)
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2.5">
                <span class="avatar avatar-xs">
                  <span class="avatar-initial rounded {{ $p['badge'] }}">
                    <i class="icon-base {{ $p['icon'] }}" style="font-size: 14px;"></i>
                  </span>
                </span>
                <div>
                  <span class="fw-bold text-heading d-block" style="font-size: 0.88rem;">{{ __($p['name']) }}</span>
                  <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;">{{ $permKey }}</small>
                  <small class="text-body-secondary d-block" style="font-size: 0.75rem;">{{ __($p['description']) }}</small>
                </div>
              </div>
            </td>

            @foreach($roles as $r)
              @php
                $rArr = is_array($r) ? $r : $r->toArray();
                $hasPerm = in_array($permKey, $rArr['permissions'] ?? [], true);
                $isAdminRole = ($rArr['slug'] === 'ADMIN');
                $isLocked = ($isAdminRole && $permKey === 'roles.manage');
                $isBailiff = str_starts_with($rArr['slug'] ?? '', 'BAILIFF');
              @endphp

              <td class="text-center {{ $isBailiff ? 'bailiff-body-col' : '' }}">
                <div class="form-check form-switch d-inline-block mb-0">
                  <input class="form-check-input matrix-switch toggle-perm-checkbox"
                         type="checkbox"
                         data-role-id="{{ $rArr['id'] }}"
                         data-perm="{{ $permKey }}"
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
                <option value="ti tabler-scroll">📜 Scroll (Questmaster Bailiff)</option>
                <option value="ti tabler-crown">👑 Crown (Administrator)</option>
                <option value="ti tabler-wand">🪄 Wand (Game Master)</option>
                <option value="ti tabler-swords">⚔️ Swords (Veteran Adventurer)</option>
                <option value="ti tabler-medal">🎖️ Medal (Member)</option>
                <option value="ti tabler-sparkles">✨ Sparkles (Special Role)</option>
                <option value="ti tabler-user">👤 User (Adventurer)</option>
              </select>
            </div>
          </div>

          <!-- Row 3: Bailiff Sub-tier Settings -->
          <div class="row g-3 mb-4 p-3 rounded-3 bg-label-secondary border">
            <div class="col-12 col-md-5">
              <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="editRoleIsBailiff">
                <label class="form-check-label fw-semibold" for="editRoleIsBailiff">
                  <i class="icon-base ti tabler-shield-check text-warning me-1"></i> {{ __('Bailiff Council Sub-tier') }}
                </label>
              </div>
            </div>
            <div class="col-12 col-md-7" id="editRoleBailiffTierWrapper">
              <label class="form-label fw-semibold small mb-1" for="editRoleBailiffTier">{{ __('Bailiff Department / Jurisdiction') }}</label>
              <input type="text" class="form-control form-control-sm" id="editRoleBailiffTier" placeholder="e.g. Discipline & Conduct">
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
            @foreach($permissions as $permKey => $p)
              <div class="col-12 col-md-6">
                <div class="card border p-3 rounded-3 h-100 bg-body">
                  <div class="form-check form-switch mb-0 d-flex align-items-start gap-2">
                    <input class="form-check-input mt-1 edit-perm-checkbox" type="checkbox" id="edit_perm_{{ str_replace('.', '_', $permKey) }}" value="{{ $permKey }}">
                    <label class="form-check-label w-100 cursor-pointer" for="edit_perm_{{ str_replace('.', '_', $permKey) }}">
                      <div class="d-flex align-items-center gap-1.5 mb-0.5">
                        <span class="badge {{ $p['badge'] }} rounded-pill" style="font-size: 0.7rem;">
                          <i class="icon-base {{ $p['icon'] }} me-0.5" style="font-size: 11px;"></i> {{ __($p['name']) }}
                        </span>
                      </div>
                      <small class="text-body-secondary d-block" style="font-size: 0.75rem; line-height: 1.4;">
                        {{ __($p['description']) }}
                      </small>
                    </label>
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
          <span>{{ __('Create New Guild Role & Bailiff Tier') }}</span>
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
              <option value="bailiff_custom">🛡️ {{ __('Custom Bailiff Tier (Priority 60)') }}</option>
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
                <option value="ti tabler-scroll">📜 Scroll (Questmaster Bailiff)</option>
                <option value="ti tabler-crown">👑 Crown (Administrator)</option>
                <option value="ti tabler-wand">🪄 Wand (Game Master)</option>
                <option value="ti tabler-swords">⚔️ Swords (Veteran Adventurer)</option>
                <option value="ti tabler-medal">🎖️ Medal (Member)</option>
                <option value="ti tabler-sparkles">✨ Sparkles (Special Role)</option>
                <option value="ti tabler-user">👤 User (Adventurer)</option>
              </select>
            </div>
          </div>

          <!-- Row 3: Bailiff Sub-tier Settings -->
          <div class="row g-3 mb-4 p-3 rounded-3 bg-label-secondary border">
            <div class="col-12 col-md-5">
              <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" id="addRoleIsBailiff">
                <label class="form-check-label fw-semibold" for="addRoleIsBailiff">
                  <i class="icon-base ti tabler-shield-check text-warning me-1"></i> {{ __('Bailiff Council Sub-tier') }}
                </label>
              </div>
            </div>
            <div class="col-12 col-md-7" id="addRoleBailiffTierWrapper" style="display: none;">
              <label class="form-label fw-semibold small mb-1" for="addRoleBailiffTier">{{ __('Bailiff Department / Jurisdiction') }}</label>
              <input type="text" class="form-control form-control-sm" id="addRoleBailiffTier" placeholder="e.g. Discipline & Conduct">
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
            @foreach($permissions as $permKey => $p)
              <div class="col-12 col-md-6">
                <div class="card border p-3 rounded-3 h-100 bg-body">
                  <div class="form-check form-switch mb-0 d-flex align-items-start gap-2">
                    <input class="form-check-input mt-1 add-perm-checkbox" type="checkbox" id="add_perm_{{ str_replace('.', '_', $permKey) }}" value="{{ $permKey }}">
                    <label class="form-check-label w-100 cursor-pointer" for="add_perm_{{ str_replace('.', '_', $permKey) }}">
                      <div class="d-flex align-items-center gap-1.5 mb-0.5">
                        <span class="badge {{ $p['badge'] }} rounded-pill" style="font-size: 0.7rem;">
                          <i class="icon-base {{ $p['icon'] }} me-0.5" style="font-size: 11px;"></i> {{ __($p['name']) }}
                        </span>
                      </div>
                      <small class="text-body-secondary d-block" style="font-size: 0.75rem; line-height: 1.4;">
                        {{ __($p['description']) }}
                      </small>
                    </label>
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

  // Bailiff switch toggles department input
  const addIsBailiff = document.getElementById('addRoleIsBailiff');
  const addTierWrapper = document.getElementById('addRoleBailiffTierWrapper');
  if (addIsBailiff && addTierWrapper) {
    addIsBailiff.addEventListener('change', function() {
      addTierWrapper.style.display = this.checked ? 'block' : 'none';
    });
  }

  const editIsBailiff = document.getElementById('editRoleIsBailiff');
  const editTierWrapper = document.getElementById('editRoleBailiffTierWrapper');
  if (editIsBailiff && editTierWrapper) {
    editIsBailiff.addEventListener('change', function() {
      editTierWrapper.style.display = this.checked ? 'block' : 'none';
    });
  }

  // Role category filter tabs
  document.querySelectorAll('.btn-role-filter').forEach(btn => {
    btn.addEventListener('click', function() {
      document.querySelectorAll('.btn-role-filter').forEach(b => b.classList.remove('active'));
      this.classList.add('active');

      const filter = this.dataset.filter;
      document.querySelectorAll('.role-item-col').forEach(cardCol => {
        const cat = cardCol.dataset.category;
        if (filter === 'all' || cat === filter) {
          cardCol.style.display = '';
        } else {
          cardCol.style.display = 'none';
        }
      });
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
          isBailiff: true,
          bailiffTier: 'Discipline & Conduct',
          perms: ['discipline.manage', 'users.manage', 'guild.manage']
        },
        bailiff_hr: {
          name: 'Hero Resources Bailiff',
          color: '#0288d1',
          priority: 65,
          desc: 'Manages adventurer intake, rank advancement, roster assignments, and progression.',
          icon: 'ti tabler-users-group',
          isBailiff: true,
          bailiffTier: 'Hero Resources & Roster',
          perms: ['hr.manage', 'xp.manage', 'users.manage', 'guild.manage']
        },
        bailiff_representative: {
          name: 'Guild Representative Bailiff',
          color: '#10b981',
          priority: 65,
          desc: 'Official guild ambassador managing diplomatic relations, external delegations, and alliance treaties.',
          icon: 'ti tabler-flag',
          isBailiff: true,
          bailiffTier: 'Diplomacy & Representation',
          perms: ['diplomacy.manage', 'guild.manage']
        },
        bailiff_archivist: {
          name: 'Archivist Bailiff',
          color: '#8b5cf6',
          priority: 65,
          desc: 'Guardian of the Great Library, guild annals, quest archives, and ancient historical scrolls.',
          icon: 'ti tabler-books',
          isBailiff: true,
          bailiffTier: 'Archives & Chronicles',
          perms: ['archives.manage', 'guild.manage']
        },
        bailiff_oathkeeper: {
          name: 'Oathkeeper Bailiff',
          color: '#06b6d4',
          priority: 65,
          desc: 'Keeper of blood bonds, initiation vows, adventurer pledges, and sacred covenants.',
          icon: 'ti tabler-shield-lock',
          isBailiff: true,
          bailiffTier: 'Sacred Oaths & Covenants',
          perms: ['oaths.manage', 'discipline.manage', 'guild.manage']
        },
        bailiff_inventory: {
          name: 'Inventory Bailiff',
          color: '#f57c00',
          priority: 60,
          desc: 'Manages the guild communal vault, magic artifacts, and treasury assets.',
          icon: 'ti tabler-package',
          isBailiff: true,
          bailiffTier: 'Vault & Treasury',
          perms: ['inventory.manage', 'guild.manage']
        },
        bailiff_questmaster: {
          name: 'Questmaster Bailiff',
          color: '#7b1fa2',
          priority: 60,
          desc: 'Supervises quest board notices, decrees, and expedition debriefs.',
          icon: 'ti tabler-scroll',
          isBailiff: true,
          bailiffTier: 'Expeditions & Decrees',
          perms: ['quests.manage', 'guild.manage']
        },
        bailiff_custom: {
          name: 'Custom Bailiff Tier',
          color: '#ff9f43',
          priority: 60,
          desc: 'Special bailiff council division officer.',
          icon: 'ti tabler-shield-check',
          isBailiff: true,
          bailiffTier: 'Custom Division',
          perms: ['guild.manage']
        },
        veteran: {
          name: 'Veteran Adventurer',
          color: '#00cfe8',
          priority: 40,
          desc: 'Hardened veterans who survived high-stakes guild expeditions.',
          icon: 'ti tabler-swords',
          isBailiff: false,
          bailiffTier: '',
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

      const isBailiffCb = document.getElementById('addRoleIsBailiff');
      const tierInput = document.getElementById('addRoleBailiffTier');
      const tierWrapperEl = document.getElementById('addRoleBailiffTierWrapper');

      if (isBailiffCb) isBailiffCb.checked = t.isBailiff;
      if (tierWrapperEl) tierWrapperEl.style.display = t.isBailiff ? 'block' : 'none';
      if (tierInput) tierInput.value = t.bailiffTier || '';

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
      const isBailiff = this.dataset.isBailiff === '1';
      const bailiffTier = this.dataset.bailiffTier || '';

      document.getElementById('editRoleId').value = id;
      document.getElementById('editRoleSlug').value = slug;
      document.getElementById('editRoleName').value = name;
      document.getElementById('editRoleDescription').value = description;
      document.getElementById('editRoleColor').value = color;
      document.getElementById('editRoleColorHex').value = color;
      document.getElementById('editRolePriority').value = priority;
      document.getElementById('editRoleIcon').value = icon;

      const editIsBailiffCb = document.getElementById('editRoleIsBailiff');
      const editTierInput = document.getElementById('editRoleBailiffTier');
      const editTierWrapperEl = document.getElementById('editRoleBailiffTierWrapper');

      if (editIsBailiffCb) editIsBailiffCb.checked = isBailiff;
      if (editTierInput) editTierInput.value = bailiffTier;
      if (editTierWrapperEl) editTierWrapperEl.style.display = isBailiff ? 'block' : 'none';

      // Reset checkboxes
      document.querySelectorAll('.edit-perm-checkbox').forEach(cb => {
        cb.checked = perms.includes(cb.value);
        cb.disabled = false;
      });

      // Guard Admin from unchecking roles.manage
      if (slug === 'ADMIN') {
        const adminPerm = document.getElementById('edit_perm_roles_manage');
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
        if (slug === 'ADMIN' && cb.value === 'roles.manage') {
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

      const isBailiffVal = document.getElementById('editRoleIsBailiff')?.checked ?? false;
      const bailiffTierVal = isBailiffVal ? document.getElementById('editRoleBailiffTier')?.value.trim() : null;

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
          Swal.fire({
            icon: 'success',
            title: '{{ __("Success!") }}',
            text: data.message,
            timer: 1500,
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

      const isBailiffVal = document.getElementById('addRoleIsBailiff')?.checked ?? false;
      const bailiffTierVal = isBailiffVal ? document.getElementById('addRoleBailiffTier')?.value.trim() : null;

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

  // Interactive Permission Matrix Quick Toggle Switch
  document.querySelectorAll('.toggle-perm-checkbox').forEach(sw => {
    sw.addEventListener('change', function() {
      const roleId = this.dataset.roleId;
      const perm = this.dataset.perm;
      const enabled = this.checked;
      const roleSlug = this.dataset.roleSlug;

      if (roleSlug === 'ADMIN' && perm === 'roles.manage' && !enabled) {
        this.checked = true;
        Swal.fire({
          icon: 'warning',
          title: '{{ __("Action Blocked") }}',
          text: '{{ __("The Administrator role must retain role management authority.") }}'
        });
        return;
      }

      fetch("{{ url('/valiant-shade/app/roles') }}/" + roleId + "/toggle-permission", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          permission: perm,
          enabled: enabled
        })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: data.message,
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true
          });
        } else {
          sw.checked = !enabled;
          Swal.fire({ icon: 'error', title: '{{ __("Error!") }}', text: data.message });
        }
      })
      .catch(() => {
        sw.checked = !enabled;
        Swal.fire({ icon: 'error', title: '{{ __("Error!") }}', text: '{{ __("Cannot reach server.") }}' });
      });
    });
  });

});
</script>
@endsection
