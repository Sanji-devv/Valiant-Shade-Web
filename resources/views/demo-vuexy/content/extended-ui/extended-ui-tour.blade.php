@extends('demo-vuexy.layouts/layoutMaster')

@section('title', 'Shepherd tour - Extended UI')

<!-- Vendor Styles -->
@section('vendor-style')
  @vite('resources/demo-vuexy/assets/vendor/libs/shepherd/shepherd.scss', 'static/demo-vuexy/build')
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
  @vite('resources/demo-vuexy/assets/vendor/libs/shepherd/shepherd.js', 'static/demo-vuexy/build')
@endsection

<!-- Page Scripts -->
@section('page-script')
  @vite('resources/demo-vuexy/assets/js/extended-ui-tour.js', 'static/demo-vuexy/build')
@endsection

@section('content')
  <div class="row">
    <div class="col-12">
      <div class="card tour-card">
        <h5 class="card-header">Tour</h5>
        <div class="card-body">
          <button class="btn btn-primary" id="shepherd-example">Start tour</button>
        </div>
      </div>
    </div>
  </div>
@endsection
