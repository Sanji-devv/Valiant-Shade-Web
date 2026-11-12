@extends('valiant-shade.layouts/layoutMaster')

@section('title', 'Account settings - Account')

<!-- Vendor Styles -->
@section('vendor-style')
@vite(['resources/valiant-shade/assets/vendor/libs/select2/select2.scss', 'resources/valiant-shade/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/valiant-shade/assets/vendor/libs/animate-css/animate.scss', 'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.scss'], 'static/valiant-shade/build')
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
@vite(['resources/valiant-shade/assets/vendor/libs/select2/select2.js', 'resources/valiant-shade/assets/vendor/libs/@form-validation/popular.js', 'resources/valiant-shade/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/valiant-shade/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/valiant-shade/assets/vendor/libs/cleave-zen/cleave-zen.js', 'resources/valiant-shade/assets/vendor/libs/sweetalert2/sweetalert2.js'], 'static/valiant-shade/build')
@endsection

<!-- Page Scripts -->
@section('page-script')
@vite(['resources/valiant-shade/assets/js/pages-account-settings-account.js'], 'static/valiant-shade/build')
@endsection

@section('content')
<div class="row">
  <div class="col-md-12">
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row mb-6 gap-md-0 gap-2">
        <li class="nav-item">
          <a class="nav-link active" href="javascript:void(0);"><i class="icon-base ti tabler-users icon-sm me-1_5"></i> Account</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="{{ route('valiant-shade.pages-profile-user') }}"><i class="icon-base ti tabler-user-check icon-sm me-1_5"></i> Profile</a>
        </li>
      </ul>
    </div>

    @if (session('success'))
      <div class="alert alert-success alert-dismissible mb-4 d-flex align-items-center" role="alert">
        <i class="icon-base ti tabler-circle-check fs-4 me-2"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if (session('error'))
      <div class="alert alert-danger alert-dismissible mb-4 d-flex align-items-center" role="alert">
        <i class="icon-base ti tabler-alert-circle fs-4 me-2"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if ($errors->any())
      <div class="alert alert-danger alert-dismissible mb-4" role="alert">
        <div class="fw-semibold mb-1">{{ __('Validation Errors:') }}</div>
        <ul class="mb-0 ps-3">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <div class="card mb-6">
      <!-- Account Header & Avatar -->
      <div class="card-body">
        <div class="d-flex align-items-start align-items-sm-center gap-6">
          <img src="{{ $user->profile_photo_url ?? asset('static/valiant-shade/assets/img/avatars/user.png') }}" alt="user-avatar" class="d-block w-px-100 h-px-100 rounded object-fit-cover shadow-sm" id="uploadedAvatar" />
          <div class="button-wrapper">
            <h4 class="mb-1 fw-bold">{{ $user->display_name ?: ($user->username ?: 'Valiant Shade') }}</h4>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              @if ($user->isAdmin())
                <span class="badge bg-label-primary font-monospace" title="{{ $user->uuid }}">UUID: {{ Str::limit($user->uuid, 16, '...') }}</span>
              @endif
              <span class="badge bg-label-secondary font-monospace">ID: #{{ $user->id }}</span>
              @forelse($user->ordered_roles as $role)
                <span class="badge rounded-pill d-inline-flex align-items-center gap-1_5 px-2_5 py-1 shadow-xs"
                      style="background-color: {{ $role->color }}20; color: {{ $role->color }}; border: 1px solid {{ $role->color }}55; font-size: 0.78rem; font-weight: 600;"
                      title="{{ $role->description ? __($role->description) : '' }} ({{ __('Authority') }}: {{ $role->priority }})">
                  <span class="badge-dot-indicator rounded-circle" style="width: 6px; height: 6px; background-color: {{ $role->color }}; display: inline-block;"></span>
                  <span>{{ __($role->name) }}</span>
                </span>
              @empty
                <span class="badge bg-label-secondary">{{ $user->role ?? 'PLAYER' }}</span>
              @endforelse
              @if ($user->current_character)
                <span class="badge bg-label-warning"><i class="icon-base ti tabler-shield me-1"></i>{{ $user->current_character }}</span>
              @endif
            </div>
            <div class="text-body-secondary small">
              <i class="icon-base ti tabler-calendar me-1"></i>{{ __('Joined:') }} {{ $user->created_at ? $user->created_at->translatedFormat('d F Y') : '-' }}
            </div>
          </div>
        </div>
      </div>

      <div class="card-body pt-2">
        <form id="formAccountSettings" method="POST" action="{{ route('valiant-shade.pages-account-settings-account.update') }}">
          @csrf
          <div class="row gy-4 gx-6 mb-6">
            <div class="col-md-6 form-control-validation">
              <label for="displayName" class="form-label">{{ __('Display Name') }} <span class="text-danger">*</span></label>
              <input class="form-control" type="text" id="displayName" name="displayName" value="{{ old('displayName', $displayName) }}" required autofocus placeholder="{{ __('e.g. ShadowBlade, Valerius') }}" />
              <div class="form-text">{{ __('Your public display name shown across the guild and party rosters.') }}</div>
            </div>

            <div class="col-md-6 form-control-validation">
              <label for="username" class="form-label">{{ __('Username') }} <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="icon-base ti tabler-at"></i></span>
                <input class="form-control font-monospace" type="text" id="username" name="username" value="{{ old('username', $user->username) }}" required autocomplete="username" />
              </div>
            </div>

            <div class="col-md-6 form-control-validation">
              <label for="email" class="form-label">{{ __('E-mail') }} <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="icon-base ti tabler-mail"></i></span>
                <input class="form-control" type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email" />
              </div>
            </div>

            <div class="col-md-6">
              <label for="current_character" class="form-label">{{ __('Active Hero / Character') }}</label>
              @if ($characters && $characters->isNotEmpty())
                <select id="current_character" name="current_character" class="select2 form-select">
                  <option value="">{{ __('No Character Selected') }}</option>
                  @foreach ($characters as $char)
                    <option value="{{ $char->character_name }}" {{ ($user->current_character === $char->character_name || old('current_character') === $char->character_name) ? 'selected' : '' }}>
                      {{ $char->character_name }} ({{ $char->class_name ?? 'Hero' }} - Lvl {{ $char->level ?? 1 }})
                    </option>
                  @endforeach
                </select>
              @else
                <input type="text" class="form-control" id="current_character" name="current_character" value="{{ old('current_character', $user->current_character) }}" placeholder="{{ __('No hero assigned yet') }}" />
              @endif
              <div class="form-text">{{ __('Select your primary character displayed in the guild.') }}</div>
            </div>

            <div class="col-md-6">
              <label for="language" class="form-label">{{ __('Language') }}</label>
              <select id="language" name="language" class="select2 form-select">
                <option value="en" {{ (app()->getLocale() === 'en' || old('language') === 'en') ? 'selected' : '' }}>English</option>
                <option value="tr" {{ (app()->getLocale() === 'tr' || old('language') === 'tr') ? 'selected' : '' }}>Türkçe</option>
              </select>
            </div>

            <div class="col-md-6">
              <label for="role" class="form-label">{{ __('Role') }}</label>
              <div class="d-flex flex-wrap gap-2" role="list" aria-label="{{ __('Assigned roles') }}">
                @forelse($user->ordered_roles as $role)
                  <span class="badge rounded-pill d-inline-flex align-items-center gap-1_5 px-3 py-2"
                        role="listitem"
                        style="background-color: {{ $role->color }}20; color: {{ $role->color }}; border: 1px solid {{ $role->color }}55;">
                    <span class="badge-dot-indicator rounded-circle" style="width: 7px; height: 7px; background-color: {{ $role->color }}; display: inline-block;"></span>
                    {{ __($role->name) }}
                  </span>
                @empty
                  <span class="badge bg-label-secondary">{{ $user->role ?? 'PLAYER' }}</span>
                @endforelse
              </div>
              <div class="form-text">{{ __('Your assigned roles are managed by an administrator.') }}</div>
            </div>

            @if ($user->isAdmin())
              <div class="col-md-12">
                <label for="uuid" class="form-label">{{ __('Unique User UUID') }}</label>
                <input type="text" class="form-control font-monospace bg-light" id="uuid" value="{{ $user->uuid }}" readonly disabled />
              </div>
            @endif
          </div>

          <div class="mt-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary waves-effect waves-light" id="btnSubmitAccount">
              <i class="icon-base ti tabler-device-floppy me-1"></i> {{ __('Save changes') }}
            </button>
            <button type="reset" class="btn btn-label-secondary waves-effect">
              <i class="icon-base ti tabler-refresh me-1"></i> {{ __('Reset') }}
            </button>
          </div>
        </form>
      </div>
      <!-- /Account -->
    </div>

    <div class="card">
      <h5 class="card-header text-danger"><i class="icon-base ti tabler-alert-triangle me-1"></i> {{ __('Deactivate Account') }}</h5>
      <div class="card-body">
        <div class="mb-6 col-12 mb-0">
          <div class="alert alert-warning">
            <h6 class="alert-heading mb-1">{{ __('Are you sure you want to deactivate your account?') }}</h6>
            <p class="mb-0">{{ __('Once deactivated, your account status will be set to inactive.') }}</p>
          </div>
        </div>
        <form id="formAccountDeactivation" method="POST" action="{{ route('valiant-shade.pages-account-settings-account.deactivate') }}">
          @csrf
          <div class="form-check my-6">
            <input class="form-check-input" type="checkbox" name="accountActivation" id="accountActivation" />
            <label class="form-check-label" for="accountActivation">{{ __('I confirm my account deactivation') }}</label>
          </div>
          <button type="submit" class="btn btn-danger deactivate-account waves-effect waves-light" disabled>
            <i class="icon-base ti tabler-user-x me-1"></i> {{ __('Deactivate Account') }}
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection