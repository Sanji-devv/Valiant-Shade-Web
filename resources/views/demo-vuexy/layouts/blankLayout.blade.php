@isset($pageConfigs)
  {!! \DemoVuexy\Helpers\Helpers::updatePageConfig($pageConfigs) !!}
@endisset

@php
  $configData = \DemoVuexy\Helpers\Helpers::appClasses();

  /* Display elements */
  $customizerHidden = $customizerHidden ?? '';
@endphp

@extends('demo-vuexy.layouts/commonMaster')

@section('layoutContent')
  <!-- Content -->
  @yield('content')
  <!--/ Content -->
@endsection
