@php
  $configData = \App\ValiantShade\Helpers\Helpers::appClasses();
  $isFront = true;
@endphp

@section('layoutContent')
  @extends('valiant-shade.layouts/commonMaster')

  @include('valiant-shade.layouts/sections/navbar/navbar-front')

  <!-- Sections:Start -->
  @yield('content')
  <!-- / Sections:End -->

  @include('valiant-shade.layouts/sections/footer/footer-front')
@endsection
