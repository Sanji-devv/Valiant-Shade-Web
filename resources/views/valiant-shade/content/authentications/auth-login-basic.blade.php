@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('valiant-shade.layouts/layoutMaster')

@section('title', 'Login Basic - Pages')

@section('vendor-style')
@vite(['resources/valiant-shade/assets/vendor/libs/@form-validation/form-validation.scss'], 'static/valiant-shade/build')
@endsection

@section('page-style')
@vite(['resources/valiant-shade/assets/vendor/scss/pages/page-auth.scss'], 'static/valiant-shade/build')
@endsection

@section('vendor-script')
@vite(['resources/valiant-shade/assets/vendor/libs/@form-validation/popular.js',
'resources/valiant-shade/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/valiant-shade/assets/vendor/libs/@form-validation/auto-focus.js'], 'static/valiant-shade/build')
@endsection

@section('page-script')
@vite(['resources/valiant-shade/assets/js/pages-auth.js'], 'static/valiant-shade/build')
@endsection

@section('content')
<div class="container-xxl position-relative">
  <!-- Theme Switcher -->
  <div class="position-absolute top-0 end-0 p-4" style="z-index: 1050;">
    <div class="dropdown">
      <button class="btn btn-icon btn-text-secondary rounded-pill dropdown-toggle hide-arrow" id="nav-theme" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="icon-base ti tabler-sun icon-22px theme-icon-active text-heading"></i>
        <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
        <li>
          <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="light">
            <span><i class="icon-base ti tabler-sun icon-22px me-3" data-icon="sun"></i>Light</span>
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark">
            <span><i class="icon-base ti tabler-moon-stars icon-22px me-3" data-icon="moon-stars"></i>Dark</span>
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system">
            <span><i class="icon-base ti tabler-device-desktop-analytics icon-22px me-3" data-icon="device-desktop-analytics"></i>System</span>
          </button>
        </li>
      </ul>
    </div>
  </div>
  <!-- /Theme Switcher -->
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <!-- Login -->
      <div class="card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center mb-6">
            <a href="{{ url('/valiant-shade') }}" class="app-brand-link">
              <span class="app-brand-logo demo">@include('valiant-shade._partials.macros')</span>
              <span class="app-brand-text demo text-heading fw-bold">{{ config('valiant-shade-variables.templateName') }}</span>
            </a>
          </div>
          <!-- /Logo -->
          <h4 class="mb-1">{{ __('Welcome to :name! 👋', ['name' => config('valiant-shade-variables.templateName')]) }}</h4>
          <p class="mb-6">{{ __('Please sign-in to your account and start the adventure') }}</p>

          @if ($errors->any())
          <div class="alert alert-danger mb-4" role="alert">
            <ul class="mb-0 ps-3">
              @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
          @endif

          <form id="formAuthentication" class="mb-4" action="{{ route('valiant-shade.auth-login-basic.post') }}" method="POST">
            @csrf
            <div class="mb-6 form-control-validation">
              <label for="email" class="form-label">{{ __('Email or Username') }}</label>
              <input type="text" class="form-control" id="email" name="email-username"
                value="{{ old('email-username') }}"
                placeholder="Enter your email or username" autofocus />
            </div>
            <div class="mb-6 form-password-toggle form-control-validation">
              <label class="form-label" for="password">{{ __('Password') }}</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control" name="password"
                  placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                  aria-describedby="password" />
                <span class="input-group-text cursor-pointer"><i class="icon-base ti tabler-eye-off"></i></span>
              </div>
            </div>
            <div class="my-8">
              <div class="d-flex justify-content-between">
                <div class="form-check mb-0 ms-2">
                  <input class="form-check-input" type="checkbox" id="remember-me" name="remember" />
                  <label class="form-check-label" for="remember-me"> {{ __('Remember Me') }} </label>
                </div>
                <a href="{{ url('/valiant-shade/auth/forgot-password-basic') }}">
                  <p class="mb-0">{{ __('Forgot Password?') }}</p>
                </a>
              </div>
            </div>
            <div class="mb-6">
              <button class="btn btn-primary d-grid w-100" type="submit">{{ __('Login') }}</button>
            </div>
          </form>

          <p class="text-center">
            <span>{{ __('New on our platform?') }}</span>
            <a href="{{ url('/valiant-shade/auth/register-basic') }}">
              <span>{{ __('Create an account') }}</span>
            </a>
          </p>

          <div class="divider my-6">
            <div class="divider-text">or</div>
          </div>

          <div class="d-flex justify-content-center">
            <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-facebook me-1_5">
              <i class="icon-base ti tabler-brand-facebook-filled icon-20px"></i>
            </a>

            <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-twitter me-1_5">
              <i class="icon-base ti tabler-brand-twitter-filled icon-20px"></i>
            </a>

            <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-github me-1_5">
              <i class="icon-base ti tabler-brand-github-filled icon-20px"></i>
            </a>

            <a href="javascript:;" class="btn btn-icon rounded-circle btn-text-google-plus">
              <i class="icon-base ti tabler-brand-google-filled icon-20px"></i>
            </a>
          </div>
        </div>
      </div>
      <!-- /Login -->
    </div>
  </div>
</div>
@endsection