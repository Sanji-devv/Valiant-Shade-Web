@php
  $configData = \DemoVuexy\Helpers\Helpers::appClasses();
  $isFront = true;
@endphp

@section('layoutContent')
  @extends('demo-vuexy.layouts/commonMaster')

  @include('demo-vuexy.layouts/sections/navbar/navbar-front')

  <!-- Sections:Start -->
  @yield('content')
  <!-- / Sections:End -->

  @include('demo-vuexy.layouts/sections/footer/footer-front')
@endsection
