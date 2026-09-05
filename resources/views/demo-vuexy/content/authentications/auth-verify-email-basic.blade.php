@php
$customizerHidden = 'customizer-hide';
@endphp

@extends('demo-vuexy.layouts/layoutMaster')

@section('title', 'Verify Email Basic - Pages')

@section('page-style')
<!-- Page -->
@vite('resources/demo-vuexy/assets/vendor/scss/pages/page-auth.scss', 'static/demo-vuexy/build')
@endsection

@section('content')
<div class="authentication-wrapper authentication-basic px-6">
  <div class="authentication-inner py-6">
    <!-- Verify Email -->
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
        <h4 class="mb-1">Verify your email ✉️</h4>
        <p class="text-start mb-0">Account activation link sent to your email address: <span
            class="fw-medium">hello@example.com</span> Please follow the link inside to continue.</p>
        <a class="btn btn-primary w-100 my-6" href="{{ url('/demo-vuexy') }}"> Skip for now </a>
        <p class="text-center mb-0">
          Didn't get the mail?
          <a href="javascript:void(0);"> Resend </a>
        </p>
      </div>
    </div>
    <!-- /Verify Email -->
  </div>
</div>
@endsection
