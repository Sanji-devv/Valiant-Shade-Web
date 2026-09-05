@isset($pageConfigs)
  {!! \App\Helpers\AdminHelpers::updatePageConfig($pageConfigs) !!}
@endisset

@php
  $configData = \App\Helpers\AdminHelpers::appClasses();

  /* Display elements */
  $customizerHidden = $customizerHidden ?? '';
@endphp

@extends('admin.layouts/commonMaster')

@section('layoutContent')
  <!-- Content -->
  @yield('content')
  <!--/ Content -->
@endsection
