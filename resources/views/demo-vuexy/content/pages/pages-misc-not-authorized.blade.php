@php
$customizerHidden = 'customizer-hide';
$configData = \DemoVuexy\Helpers\Helpers::appClasses();
@endphp

@extends('demo-vuexy.layouts/layoutMaster')

@section('title', 'Not Authorized - Pages')

@section('page-style')
<!-- Page -->
@vite(['resources/demo-vuexy/assets/vendor/scss/pages/page-misc.scss'], 'static/demo-vuexy/build')
@endsection


@section('content')
<!-- Not Authorized -->
<div class="container-xxl container-p-y">
  <div class="misc-wrapper">
    <h1 class="mb-2 mx-2" style="line-height: 6rem;font-size: 6rem;">401</h1>
    <h4 class="mb-2 mx-2">You are not authorized! 🔐</h4>
    <p class="mb-6 mx-2">You don’t have permission to access this page. Go Home!</p>
    <a href="{{ url('/demo-vuexy') }}" class="btn btn-primary">Back to home</a>
    <div class="mt-12">
      <img src="{{ asset('static/demo-vuexy/assets/img/illustrations/page-misc-you-are-not-authorized.png') }}" alt="page-misc-not-authorized" width="170" class="img-fluid" />
    </div>
  </div>
</div>
<div class="container-fluid misc-bg-wrapper">
  <img src="{{ asset('static/demo-vuexy/assets/img/illustrations/bg-shape-image-' . $configData['theme'] . '.png') }}" height="355" alt="page-misc-not-authorized" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.png" />
</div>
<!-- /Not Authorized -->
@endsection