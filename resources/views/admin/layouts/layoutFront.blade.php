@php
  $configData = \App\Helpers\AdminHelpers::appClasses();
  $isFront = true;
@endphp

@section('layoutContent')
  @extends('admin.layouts/commonMaster')

  @include('admin.layouts/sections/navbar/navbar-front')

  <!-- Sections:Start -->
  @yield('content')
  <!-- / Sections:End -->

  @include('admin.layouts/sections/footer/footer-front')
@endsection
