@php
$customizerHidden = 'customizer-hide';
@endphp
@extends('demo-vuexy.layouts/layoutMaster')

@section('title', 'Forgot Password Basic - Pages')

@section('vendor-style')
@vite(['resources/demo-vuexy/assets/vendor/libs/@form-validation/form-validation.scss'], 'static/demo-vuexy/build')
@endsection

@section('page-style')
@vite(['resources/demo-vuexy/assets/vendor/scss/pages/page-auth.scss'], 'static/demo-vuexy/build')
@endsection

@section('vendor-script')
@vite(['resources/demo-vuexy/assets/vendor/libs/@form-validation/popular.js',
'resources/demo-vuexy/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/demo-vuexy/assets/vendor/libs/@form-validation/auto-focus.js'], 'static/demo-vuexy/build')
@endsection

@section('page-script')
@vite(['resources/demo-vuexy/assets/js/pages-auth.js'], 'static/demo-vuexy/build')
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner py-6">
      <!-- Forgot Password -->
      <div class="card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center mb-6">
            <a href="{{ url('/demo-vuexy') }}" class="app-brand-link">
              <span class="app-brand-logo demo">@include('demo-vuexy._partials.macros')</span>
              <span class="app-brand-text demo text-heading fw-bold">{{ config('demo-vuexy-variables.templateName') }}</span>
            </a>
          </div>
          <!-- /Logo -->
          <h4 class="mb-1">Forgot Password? 🔒</h4>
          <p class="mb-6">Enter your email and we'll send you instructions to reset your password</p>
          <form id="formAuthentication" class="mb-6" action="{{ url('/demo-vuexy/auth/reset-password-basic') }}" method="GET">
            <div class="mb-6 form-control-validation">
              <label for="email" class="form-label">Email</label>
              <input type="text" class="form-control" id="email" name="email" placeholder="Enter your email"
                autofocus />
            </div>
            <button class="btn btn-primary d-grid w-100">Send Reset Link</button>
          </form>
          <div class="text-center">
            <a href="{{ url('/demo-vuexy/auth/login-basic') }}" class="d-flex justify-content-center">
              <i class="icon-base ti tabler-chevron-left scaleX-n1-rtl me-1_5"></i>
              Back to login
            </a>
          </div>
        </div>
      </div>
      <!-- /Forgot Password -->
    </div>
  </div>
</div>
@endsection