@isset($pageConfigs)
  {!! \App\ValiantShade\Helpers\Helpers::updatePageConfig($pageConfigs) !!}
@endisset

@php
  $configData = \App\ValiantShade\Helpers\Helpers::appClasses();

  /* Display elements */
  $customizerHidden = $customizerHidden ?? '';
@endphp

@extends('valiant-shade.layouts/commonMaster')

@section('layoutContent')
  <!-- Content -->
  @yield('content')
  <!--/ Content -->
@endsection
