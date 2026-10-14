@extends('valiant-shade.layouts/layoutMaster')

@section('title', 'User List - Valiant Shade')

@section('vendor-style')
@vite([
  'resources/valiant-shade/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/valiant-shade/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/valiant-shade/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
  'resources/valiant-shade/assets/vendor/libs/select2/select2.scss',
  'resources/valiant-shade/assets/vendor/libs/@form-validation/form-validation.scss'
], 'static/valiant-shade/build')
@endsection

@section('vendor-script')
@vite([
  'resources/valiant-shade/assets/vendor/libs/moment/moment.js',
  'resources/valiant-shade/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/valiant-shade/assets/vendor/libs/select2/select2.js',
  'resources/valiant-shade/assets/vendor/libs/@form-validation/popular.js',
  'resources/valiant-shade/assets/vendor/libs/@form-validation/bootstrap5.js',
  'resources/valiant-shade/assets/vendor/libs/@form-validation/auto-focus.js',
  'resources/valiant-shade/assets/vendor/libs/cleave-zen/cleave-zen.js'
], 'static/valiant-shade/build')
@endsection

@section('page-script')
<script>
  window.currentUserIsAdmin = {{ !empty($isAdmin) ? 'true' : 'false' }};
  window.currentUserCanManageRoles = {{ !empty($canManageRoles) ? 'true' : 'false' }};
</script>
@vite('resources/valiant-shade/assets/js/app-user-list.js', 'static/valiant-shade/build')
@endsection

@section('content')
<!-- Statistical Metric Cards -->
<div class="row g-6 mb-6">
  <!-- Total Users -->
  <div class="col-sm-6 col-xl-3">
    <div class="card shadow-none border">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading fw-semibold">Total Users</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalUsers }}</h4>
              <span class="badge bg-label-primary font-size-12">Active: {{ $activeUsers }}</span>
            </div>
            <small class="text-body-secondary mb-0">All registered accounts</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-primary">
              <i class="icon-base ti tabler-users icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Administrators (Admin) -->
  <div class="col-sm-6 col-xl-3">
    <div class="card shadow-none border">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading fw-semibold">Administrators</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $adminUsers }}</h4>
              <span class="badge bg-label-danger font-size-12">Full Access</span>
            </div>
            <small class="text-body-secondary mb-0">Users with Admin role</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-danger">
              <i class="icon-base ti tabler-crown icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bailiffs -->
  <div class="col-sm-6 col-xl-3">
    <div class="card shadow-none border">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading fw-semibold">Bailiffs</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $bailiffUsers }}</h4>
              <span class="badge bg-label-warning font-size-12">Moderation</span>
            </div>
            <small class="text-body-secondary mb-0">Guards with Bailiff role</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-warning">
              <i class="icon-base ti tabler-shield icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Standard Members (User) -->
  <div class="col-sm-6 col-xl-3">
    <div class="card shadow-none border">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading fw-semibold">Standard Members</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $standardUsers }}</h4>
              <span class="badge bg-label-info font-size-12">Players</span>
            </div>
            <small class="text-body-secondary mb-0">Members with User role</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded-3 bg-label-info">
              <i class="icon-base ti tabler-user icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- User List Table and Filters -->
<div class="card">
  <div class="card-header border-bottom">
    <div class="d-flex align-items-center justify-content-between gap-3">
      <h5 class="card-title mb-0">User Filters</h5>
      @if ($canManageRoles)
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#roleManagementModal">
          <i class="icon-base ti tabler-shield-cog me-1"></i>Manage Roles
        </button>
      @endif
    </div>
    <div class="d-flex justify-content-between align-items-center row pt-4 gap-4 gap-md-0">
      <div class="col-md-6 user_role"></div>
      <div class="col-md-6 user_status"></div>
    </div>
  </div>
  <div class="card-datatable table-responsive">
    <table class="datatables-users table">
      <thead class="border-top">
        <tr>
          <th></th>
          <th></th>
          <th>User</th>
          <th>Role</th>
          <th>Active Character</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
    </table>
  </div>

  <!-- Add New User Offcanvas -->
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddUser" aria-labelledby="offcanvasAddUserLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvasAddUserLabel" class="offcanvas-title">Add New User</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
      <form class="add-new-user pt-0" id="addNewUserForm" onsubmit="return false">
        <!-- Display Name -->
        <div class="mb-6 form-control-validation">
          <label class="form-label" for="add-user-fullname">{{ __('Display Name') }} <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="add-user-fullname" placeholder="Valerius Thorn" name="userFullname" required />
        </div>

        <!-- Email -->
        <div class="mb-6 form-control-validation">
          <label class="form-label" for="add-user-email">Email <span class="text-danger">*</span></label>
          <input type="email" id="add-user-email" class="form-control" placeholder="valerius@valiantshade.com" name="userEmail" required />
        </div>

        <!-- Active Character -->
        <div class="mb-6">
          <label class="form-label" for="add-user-character">Active Character</label>
          <div class="input-group input-group-merge">
            <span class="input-group-text"><i class="icon-base ti tabler-swords"></i></span>
            <input type="text" id="add-user-character" class="form-control" placeholder="e.g. Valerius (Barbarian)" name="currentCharacter" />
          </div>
          <div class="form-text text-muted">User's active character in Valiant Shade</div>
        </div>

        <!-- Role -->
        <div class="mb-6">
          <label class="form-label fw-semibold" for="user-role">User Role <span class="text-danger">*</span></label>
          <select id="user-role" name="userRole" class="form-select">
            @foreach ($roles as $role)
              <option value="{{ $role->slug }}" @selected($role->slug === 'PLAYER')>{{ $role->name }}</option>
            @endforeach
          </select>
          <div class="form-text text-muted">Choose the role assigned to this user.</div>
        </div>

        <div class="pt-2">
          <button type="submit" class="btn btn-primary me-3 data-submit waves-effect waves-light">
            <i class="icon-base ti tabler-check me-1"></i> Submit
          </button>
          <button type="reset" class="btn btn-label-danger waves-effect" data-bs-dismiss="offcanvas">
            <i class="icon-base ti tabler-x me-1"></i> Cancel
          </button>
        </div>
      </form>
    </div>
  </div>

  @if ($canManageRoles)
    <div class="modal fade" id="userAccessModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">{{ __('User roles & direct permissions') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="user-access-form">
            <div class="modal-body">
              <input type="hidden" id="user-access-id">
              <h6 class="mb-2">{{ __('Assigned roles') }}</h6>
              <div id="user-access-roles" class="d-grid gap-2 border rounded p-3 mb-4" style="max-height: 160px; overflow-y: auto;"></div>
              <h6 class="mb-2">{{ __('Direct permissions (override / extra)') }}</h6>
              <p class="small text-body-secondary">{{ __('These apply in addition to role permissions. Use module.all for full access to a route.') }}</p>
              <div id="user-access-permissions" class="d-grid gap-2 border rounded p-3" style="max-height: 240px; overflow-y: auto;"></div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
              <button type="submit" class="btn btn-primary">{{ __('Save access') }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="roleManagementModal" tabindex="-1" aria-labelledby="roleManagementModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="roleManagementModalLabel">Role Management</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row g-5">
              <div class="col-lg-5">
                <h6>Roles</h6>
                <div id="role-list" class="list-group"></div>
              </div>
              <div class="col-lg-7">
                <form id="role-form">
                  <input type="hidden" name="roleId" id="role-id">
                  <div class="row g-3 mb-3">
                    <div class="col-md-7">
                      <label class="form-label" for="role-name">{{ __('Role Name') }} <span class="text-danger">*</span></label>
                      <input class="form-control" id="role-name" name="name" maxlength="60" required placeholder="e.g. Game Master">
                    </div>
                    <div class="col-md-5">
                      <label class="form-label" for="role-priority">{{ __('Authority Priority') }} <span class="text-danger">*</span></label>
                      <input type="number" class="form-control" id="role-priority" name="priority" min="0" max="100" value="10" placeholder="10-100" title="{{ __('Higher number = Higher authority (Admin: 100, Player: 10)') }}">
                      <div class="form-text" style="font-size: 0.72rem;">{{ __('100: Supreme, 10: Player') }}</div>
                    </div>
                  </div>
                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <label class="form-label" for="role-color">{{ __('Role Badge Color') }}</label>
                      <div class="input-group">
                        <input type="color" class="form-control form-control-color" id="role-color" name="color" value="#7367f0" title="{{ __('Choose role color') }}">
                        <input type="text" class="form-control" id="role-color-hex" value="#7367f0" placeholder="#7367f0">
                      </div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label" for="role-icon">{{ __('Icon Class') }}</label>
                      <input class="form-control" id="role-icon" name="icon" placeholder="ti tabler-shield" value="ti tabler-shield">
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label" for="role-description">{{ __('Description') }}</label>
                    <textarea class="form-control" id="role-description" name="description" rows="2" placeholder="Guild role purpose..."></textarea>
                  </div>
                  <h6>{{ __('Permissions') }}</h6>
                  <div id="role-permissions" class="d-grid gap-2 border rounded p-3 bg-light bg-opacity-10 mb-3" style="max-height: 180px; overflow-y: auto;"></div>
                  <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary" type="submit"><i class="icon-base ti tabler-device-floppy me-1"></i>{{ __('Save Role') }}</button>
                    <button class="btn btn-label-secondary" type="button" id="new-role">{{ __('New Role') }}</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif
</div>
@endsection
