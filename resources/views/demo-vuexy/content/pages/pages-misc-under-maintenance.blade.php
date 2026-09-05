@php
$customizerHidden = 'customizer-hide';
$configData = \DemoVuexy\Helpers\Helpers::appClasses();
@endphp

@extends('demo-vuexy.layouts/layoutMaster')

@section('title', 'Under Maintenance - Pages')

@section('page-style')
<!-- Page -->
@vite(['resources/demo-vuexy/assets/vendor/scss/pages/page-misc.scss'], 'static/demo-vuexy/build')
@endsection

@section('content')
<!--Under Maintenance -->
<div class="container-xxl container-p-y">
  <div class="misc-wrapper">
    <h4 class="mb-2 mx-2">Under Maintenance! 🚧</h4>
    <p class="mb-6 mx-2">Sorry for the inconvenience but we're performing some maintenance at the moment</p>
    <a href="{{ url('/demo-vuexy') }}" class="btn btn-primary">Back to home</a>
    <div class="mt-12">
      <img src="{{ asset('static/demo-vuexy/assets/img/illustrations/page-misc-under-maintenance.png') }}" alt="page-misc-under-maintenance" width="550" class="img-fluid" />
    </div>
  </div>
</div>
<div class="container-fluid misc-bg-wrapper misc-under-maintenance-bg-wrapper">
  <img src="{{ asset('static/demo-vuexy/assets/img/illustrations/bg-shape-image-' . $configData['theme'] . '.png') }}" height="355" alt="page-misc-under-maintenance" data-app-light-img="illustrations/bg-shape-image-light.png" data-app-dark-img="illustrations/bg-shape-image-dark.png" />
</div>
<!-- /Under Maintenance -->
@endsection